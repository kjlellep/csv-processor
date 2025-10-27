<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\Canonical;

class UpdateRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['sometimes','string','in:MULTIPLY,REMOVE,REGEX'],
            'position' => ['sometimes','integer','min:0'],
            'target' => [
                'nullable',
                'string',
                function($a,$v,$fail){ if($v!==null && !Canonical::isCanonical($v)) $fail('Invalid target'); }
            ],
            'config' => ['sometimes','array'],
            'enabled'=> ['sometimes','boolean'],
        ];
    }
}
