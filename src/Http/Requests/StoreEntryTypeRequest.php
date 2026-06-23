<?php

namespace Numerar\Contable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntryTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', 'unique:accounting_entry_types,code'],
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código del tipo de comprobante es obligatorio.',
            'code.unique'   => 'Ya existe un tipo de comprobante con ese código.',
            'name.required' => 'El nombre del tipo de comprobante es obligatorio.',
        ];
    }
}
