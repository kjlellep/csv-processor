<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id',
        'original_filename',
        'rows_total',
        'processed_at',
        'status',
        'error_message',
        'source_hash',
    ];

    protected $casts = [
        'rows_total'   => 'integer',
        'processed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ProcessedRow::class);
    }
}
