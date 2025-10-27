<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\Canonical;

class UpdateVendorRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->vendor->id ?? null;
        return [
            'name' => ['sometimes','string','max:255',"unique:vendors,name,$id,id"],
            'export_csv' => ['sometimes','boolean'],
            'mappings' => ['sometimes','array'],
            'mappings.*' => ['string','min:1'],
            'export_columns' => ['sometimes','array','min:1'],
            'export_columns.*' => ['string', function($a,$v,$fail){ if(!Canonical::isCanonical($v)) $fail("Invalid canonical: $v"); }],
            'csv_delimiter' => ['sometimes','string','size:1'],
            'csv_enclosure' => ['sometimes','string','size:1'],
            'csv_escape'    => ['sometimes','string','size:1'],
            'has_header'    => ['sometimes','boolean'],
        ];
    }
}
