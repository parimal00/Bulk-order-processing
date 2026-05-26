<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderIntegration extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_id',
        'provider',
        'status',
        'internal_status',
        'external_status',
        'external_reference',
        'attempt_count',
        'last_error',
        'request_payload',
        'response_payload',
        'sent_at',
        'last_callback_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'sent_at' => 'datetime',
            'last_callback_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
