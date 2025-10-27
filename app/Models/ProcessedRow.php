<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessedRow extends Model
{
    protected $table = 'processed_rows';

    protected $fillable = [
        'upload_id',
        'vendor_id',
        'product_name',
        'quantity',
        'price',
        'sku',
        'raw_source',
    ];

    protected $casts = [
        'quantity'  => 'integer',
        'price'     => 'decimal:2',
        'raw_source'=> 'array',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
