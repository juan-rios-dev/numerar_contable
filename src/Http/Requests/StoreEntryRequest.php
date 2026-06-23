<?php

namespace Numerar\Contable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entry_type'               => ['required', 'string', 'exists:accounting_entry_types,code'],
            'entry_sequence_id'        => ['nullable', 'integer', 'exists:accounting_entry_sequences,id'],
            'date'                     => ['required', 'date'],
            'description'              => ['nullable', 'string', 'max:500'],
            'lines'                    => ['required', 'array', 'min:2'],
            'lines.*.account_id'       => ['required', 'exists:accounts,id'],
            'lines.*.debit'            => ['required', 'numeric', 'min:0'],
            'lines.*.credit'           => ['required', 'numeric', 'min:0'],
            'lines.*.description'      => ['nullable', 'string', 'max:255'],
            'lines.*.third_party_type' => ['nullable', 'string', 'max:255'],
            'lines.*.third_party_id'   => ['nullable', 'integer'],
            'lines.*.cost_center_id'   => ['nullable', 'exists:cost_centers,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'entry_type.required'         => 'El tipo de comprobante es obligatorio.',
            'entry_type.exists'           => 'El tipo de comprobante seleccionado no existe.',
            'entry_sequence_id.exists'    => 'La numeración seleccionada no existe.',
            'date.required'               => 'La fecha del comprobante es obligatoria.',
            'lines.required'              => 'El comprobante debe tener al menos 2 líneas.',
            'lines.min'                   => 'El comprobante debe tener al menos 2 líneas.',
            'lines.*.account_id.required' => 'Cada línea debe tener una cuenta contable.',
            'lines.*.account_id.exists'   => 'Una de las cuentas seleccionadas no existe.',
            'lines.*.debit.numeric'       => 'El débito debe ser un valor numérico.',
            'lines.*.credit.numeric'      => 'El crédito debe ser un valor numérico.',
        ];
    }
}
