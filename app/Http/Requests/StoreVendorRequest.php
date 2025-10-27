<?php
namespace App\Http\Requests;

use App\Support\Canonical;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreVendorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required','string','max:255','unique:vendors,name'],
            'export_csv' => ['boolean'],
            'mappings' => ['required','array'],
            'mappings.*' => ['string','min:1'],
            'export_columns' => ['required','array','min:1'],
            'export_columns.*' => [
                'string',
                function($a,$v,$fail){ if(!Canonical::isCanonical($v)) $fail("Invalid canonical: $v"); }
            ],
            'csv_delimiter' => ['sometimes','string','size:1'],
            'csv_enclosure' => ['sometimes','string','size:1'],
            'csv_escape'    => ['sometimes','string','size:1'],
            'has_header'    => ['boolean'],
        ];
    }

    protected function passedValidation(): void
    {
        foreach (array_keys((array)$this->input('mappings',[])) as $k) {
            if (!Canonical::isCanonical($k)) {
                throw ValidationException::withMessages([
                    'mappings' => ["Invalid canonical key: $k"],
                ]);
            }
        }
        $cols = (array)$this->input('export_columns',[]);
        if (count($cols) !== count(array_unique($cols))) {
            throw ValidationException::withMessages([
                'export_columns' => ['Duplicate values'],
            ]);
        }
    }
}
