<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationError extends Model
{
    use HasFactory;

    protected $fillable = [
        'bulk_upload_id',
        'row_number',
        'column_name',
        'error_code',
        'error_message',
        'raw_value',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'context' => 'array',
        ];
    }

    public function bulkUpload(): BelongsTo
    {
        return $this->belongsTo(BulkUpload::class);
    }
}
