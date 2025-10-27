<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'export_csv',
        'mappings',
        'export_columns',
        'csv_delimiter',
        'csv_enclosure',
        'csv_escape',
        'has_header',
    ];

    protected $casts = [
        'export_csv'     => 'boolean',
        'mappings'       => 'array',
        'export_columns' => 'array',
        'has_header'     => 'boolean',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(VendorRule::class)->orderBy('position');
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }
}
