<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class SlowQueryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Log queries taking longer than the threshold in milliseconds (defaults to 500ms)
        $threshold = config('database.slow_query_threshold', 500);

        DB::listen(function (QueryExecuted $query) use ($threshold) {
            if ($query->time >= $threshold) {
                $sql = $query->sql;
                $bindings = $query->bindings;

                // Safely format bindings in the sql statement for easy debugging in logs
                try {
                    foreach ($bindings as $binding) {
                        $value = is_numeric($binding) ? $binding : "'{$binding}'";
                        $sql = preg_replace('/\?/', (string) $value, $sql, 1);
                    }
                } catch (\Throwable) {
                    // Fallback to original SQL if parsing bindings fails
                }

                Log::warning("Slow Query Detected ({$query->time}ms)", [
                    'sql' => $sql,
                    'raw_sql' => $query->sql,
                    'bindings' => $bindings,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'user_id' => auth()->id() ?? 'guest',
                ]);
            }
        });
    }
}
