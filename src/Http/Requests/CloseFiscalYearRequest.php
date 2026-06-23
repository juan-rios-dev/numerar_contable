<?php

namespace Numerar\Contable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseFiscalYearRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entry_type'        => ['required', 'string', 'exists:accounting_entry_types,code'],
            'entry_sequence_id' => ['nullable', 'integer', 'exists:accounting_entry_sequences,id'],
            'equity_account_id' => ['required', 'integer', 'exists:accounts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'entry_type.required'        => 'El tipo de comprobante es obligatorio.',
            'entry_type.exists'          => 'El tipo de comprobante seleccionado no existe.',
            'equity_account_id.required' => 'Debe seleccionar la cuenta de patrimonio.',
            'equity_account_id.exists'   => 'La cuenta de patrimonio seleccionada no existe.',
        ];
    }
}
