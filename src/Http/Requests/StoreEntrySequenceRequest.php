<?php

namespace Numerar\Contable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntrySequenceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:100'],
            'prefix'         => ['required', 'string', 'max:20'],
            'initial_number' => ['required', 'integer', 'min:1'],
            'priority'       => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'El nombre de la numeración es obligatorio.',
            'prefix.required'         => 'El prefijo es obligatorio.',
            'initial_number.required' => 'El número inicial es obligatorio.',
            'initial_number.min'      => 'El número inicial debe ser mayor a cero.',
            'priority.required'       => 'La prioridad es obligatoria.',
        ];
    }
}
