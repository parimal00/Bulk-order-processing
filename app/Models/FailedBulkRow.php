<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailedBulkRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'bulk_upload_id',
        'row_number',
        'sku',
        'quantity',
        'error_code',
        'error_message',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function bulkUpload(): BelongsTo
    {
        return $this->belongsTo(BulkUpload::class);
    }
}
