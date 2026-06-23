<?php

namespace Numerar\Contable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountClassRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $ignoreId = $this->route('accountClass')?->id;

        return [
            'code'   => ['required', 'string', 'max:2', "unique:account_classes,code,{$ignoreId}"],
            'name'   => ['required', 'string', 'max:255'],
            'nature' => ['required', 'in:DEBIT,CREDIT'],
            'active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'   => 'El código de clase es obligatorio.',
            'code.unique'     => 'Ya existe una clase con ese código.',
            'name.required'   => 'El nombre de la clase es obligatorio.',
            'nature.required' => 'La naturaleza contable es obligatoria.',
            'nature.in'       => 'La naturaleza debe ser DEBIT o CREDIT.',
        ];
    }
}
