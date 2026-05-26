<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_APPROVED = 'approved'; // Represents any final approved state (e.g., allocated, partially_fulfilled)
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'bulk_upload_id',
        'customer_id',
        'created_by',
        'order_number',
        'status',
        'currency',
        'subtotal',
        'total',
        'source_row_number',
        'placed_at',
        'policy_flags',
        'projected_margin',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'source_row_number' => 'integer',
            'placed_at' => 'datetime',
            'policy_flags' => 'array',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function bulkUpload(): BelongsTo
    {
        return $this->belongsTo(BulkUpload::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(OrderIntegration::class);
    }

    /**
     * Determine what the order status should be based on its lines' stock allocation.
     * Optionally accepts allocated and backordered quantities (e.g. during bulk import).
     */
    public function determineFulfillmentStatus(?int $totalAllocated = null, ?int $totalBackorder = null): string
    {
        if ($totalAllocated === null || $totalBackorder === null) {
            $totalAllocated = (int) $this->lines()->sum('allocated_quantity');
            $totalBackorder = (int) $this->lines()->sum('backorder_quantity');
        }

        if ($totalBackorder === 0) {
            return 'allocated';
        } elseif ($totalAllocated > 0) {
            return 'partially_fulfilled';
        }
        
        return 'backordered';
    }

    /**
     * Get the dynamic list of policy flags, including runtime flags like large order value.
     *
     * @return string[]
     */
    public function getAllPolicyFlagsAttribute(): array
    {
        $flags = $this->policy_flags ?? [];

        // Dynamically flag large orders if not already flagged by engine
        if ($this->total > 10000 && !in_array('Large Order Value', $flags)) {
            $flags[] = 'Large Order Value';
        }

        return $flags;
    }

    /**
     * Determine the risk level of the order based on triggered flags.
     */
    public function getRiskLevelAttribute(): string
    {
        $flags = $this->all_policy_flags;

        if (count($flags) === 0) {
            return 'low';
        }

        foreach ($flags as $flag) {
            if (str_contains(strtolower($flag), 'moq') || str_contains(strtolower($flag), 'large')) {
                return 'high';
            }
        }

        return 'medium';
    }

    /**
     * Determine if the order is in a finalised state (approved or rejected).
     * Checks both status constants and timestamp columns for safety.
     */
    public function isFinalised(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED])
            || $this->approved_at !== null
            || $this->rejected_at !== null;
    }
}
