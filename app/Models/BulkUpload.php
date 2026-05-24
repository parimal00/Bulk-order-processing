<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkUpload extends Model
{
    use HasFactory;

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_VALIDATING = 'validating';

    public const STATUS_VALID = 'valid';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_FAILED_ROWS = 'failed_rows';

    public const STATUS_PROCESSED = 'processed';

    protected $fillable = [
        'customer_id',
        'uploaded_by',
        'original_filename',
        'storage_path',
        'file_hash',
        'file_type',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'column_mapping',
        'meta',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'invalid_rows' => 'integer',
            'column_mapping' => 'array',
            'meta' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function validationErrors(): HasMany
    {
        return $this->hasMany(ValidationError::class);
    }
    public function failedRows(): HasMany
    {
        return $this->hasMany(FailedBulkRow::class);
    }

    }

