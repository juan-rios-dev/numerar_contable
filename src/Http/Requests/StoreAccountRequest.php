<?php

namespace Numerar\Contable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $ignoreId = $this->route('account')?->id;

        return [
            'parent_id'            => ['nullable', 'exists:accounts,id'],
            'class_id'             => ['required', 'exists:account_classes,id'],
            'code'                 => ['nullable', 'string', 'max:20', "unique:accounts,code,{$ignoreId}"],
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:1000'],
            'nature'               => ['required', 'in:DEBIT,CREDIT'],
            'account_type'         => ['required', 'in:MAYOR,MOVIMIENTO'],
            'active'               => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'class_id.required' => 'Debe seleccionar una clase contable.',
            'class_id.exists'   => 'La clase contable seleccionada no existe.',
            'code.unique'       => 'Ya existe una cuenta con ese código.',
            'name.required'     => 'El nombre de la cuenta es obligatorio.',
            'nature.required'   => 'La naturaleza contable es obligatoria.',
            'nature.in'         => 'La naturaleza debe ser DEBIT o CREDIT.',
            'account_type.in'   => 'El tipo debe ser MAYOR o MOVIMIENTO.',
        ];
    }
}
