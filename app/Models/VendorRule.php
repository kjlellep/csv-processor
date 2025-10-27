<?php

namespace App\Models;

use App\Enums\RuleType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRule extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'vendor_rules';

    protected $fillable = [
        'vendor_id',
        'type',
        'position',
        'target',
        'config',
        'enabled',
    ];

    protected $casts = [
        'type'    => RuleType::class,
        'position'=> 'integer',
        'config'  => 'array',
        'enabled' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
