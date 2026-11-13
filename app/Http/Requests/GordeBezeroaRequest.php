<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GordeBezeroaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'izena' => ['required', 'string', 'max:255'],
            'emaila' => ['required', 'email', 'max:255', 'unique:bezeroak,emaila'],
            'telefonoa' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'izena.required' => 'Izena derrigorrezkoa da.',
            'emaila.required' => 'Emaila derrigorrezkoa da.',
            'emaila.email' => 'Emailak ez du formatu egokia.',
            'emaila.unique' => 'Email hori duen bezero bat badago jada.',
        ];
    }
}
