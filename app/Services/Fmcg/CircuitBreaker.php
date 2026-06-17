<?php

namespace App\Services\Fmcg;

use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    public const COOLDOWN_SECONDS = 60;
    public const MAX_FAILURES = 5;

    /**
     * Get the internal state of the circuit breaker for a provider.
     * Can be: 'closed', 'open', 'paused', 'half-open'.
     */
    public function getState(string $provider): string
    {
        $state = Cache::get($this->stateKey($provider), 'closed');

        if ($state === 'open') {
            $trippedAt = Cache::get($this->trippedAtKey($provider));
            if ($trippedAt && now()->diffInSeconds($trippedAt) >= self::COOLDOWN_SECONDS) {
                // Cooldown elapsed, transition to half-open
                $state = 'half-open';
                Cache::put($this->stateKey($provider), 'half-open');
            }
        }

        return $state;
    }

    /**
     * Get the user-friendly status of the circuit breaker.
     * Can be: 'Active', 'Paused', 'Tripped'.
     */
    public function getStatus(string $provider): string
    {
        $state = $this->getState($provider);

        if ($state === 'paused') {
            return 'Paused';
        }

        if ($state === 'open') {
            return 'Tripped';
        }

        return 'Active';
    }

    /**
     * Check if the circuit breaker allows requests to flow.
     */
    public function isAvailable(string $provider): bool
    {
        $state = $this->getState($provider);

        return $state === 'closed' || $state === 'half-open';
    }

    /**
     * Record a successful response.
     */
    public function recordSuccess(string $provider): void
    {
        $state = $this->getState($provider);

        if ($state === 'paused') {
            return;
        }

        Cache::forget($this->failuresKey($provider));
        Cache::forget($this->trippedAtKey($provider));
        Cache::put($this->stateKey($provider), 'closed');
    }

    /**
     * Record a failure.
     */
    public function recordFailure(string $provider): void
    {
        $state = $this->getState($provider);

        if ($state === 'paused') {
            return;
        }

        if ($state === 'half-open') {
            $this->trip($provider);
            return;
        }

        $failures = (int) Cache::increment($this->failuresKey($provider));
        Cache::put($this->failuresKey($provider), $failures, 3600);

        if ($failures >= self::MAX_FAILURES) {
            $this->trip($provider);
        }
    }

    /**
     * Trip the circuit breaker to the open state.
     */
    private function trip(string $provider): void
    {
        Cache::put($this->stateKey($provider), 'open');
        Cache::put($this->trippedAtKey($provider), now(), 3600);
    }

    /**
     * Manually pause the circuit breaker.
     */
    public function pause(string $provider): void
    {
        Cache::put($this->stateKey($provider), 'paused');
    }

    /**
     * Manually resume/enable the circuit breaker.
     */
    public function resume(string $provider): void
    {
        $this->reset($provider);
    }

    /**
     * Reset the circuit breaker state and failures count.
     */
    public function reset(string $provider): void
    {
        Cache::forget($this->failuresKey($provider));
        Cache::forget($this->trippedAtKey($provider));
        Cache::put($this->stateKey($provider), 'closed');
    }

    /**
     * Get the remaining cooldown seconds if tripped.
     */
    public function getCooldownRemaining(string $provider): int
    {
        $state = Cache::get($this->stateKey($provider), 'closed');

        if ($state !== 'open') {
            return 0;
        }

        $trippedAt = Cache::get($this->trippedAtKey($provider));
        if (! $trippedAt) {
            return 0;
        }

        $diff = now()->diffInSeconds($trippedAt);
        return (int) max(0, self::COOLDOWN_SECONDS - $diff);
    }

    private function stateKey(string $provider): string
    {
        return "circuit_breaker:{$provider}:state";
    }

    private function failuresKey(string $provider): string
    {
        return "circuit_breaker:{$provider}:failures";
    }

    private function trippedAtKey(string $provider): string
    {
        return "circuit_breaker:{$provider}:tripped_at";
    }
}
