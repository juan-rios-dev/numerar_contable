<?php

namespace Numerar\Contable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCostCenterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $ignoreId = $this->route('costCenter')?->id;

        return [
            'code'        => ['required', 'string', 'max:20', "unique:cost_centers,code,{$ignoreId}"],
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active'      => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código del centro de costo es obligatorio.',
            'code.unique'   => 'Ya existe un centro de costo con ese código.',
            'name.required' => 'El nombre del centro de costo es obligatorio.',
        ];
    }
}
