<?php

namespace Numerar\Contable\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StorePeriodRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if ($this->filled('year') && $this->filled('month') && ! $this->filled('start_date')) {
            $start = Carbon::create((int) $this->year, (int) $this->month, 1);
            $this->merge([
                'start_date' => $start->toDateString(),
                'end_date'   => $start->copy()->endOfMonth()->toDateString(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'year'       => ['required', 'integer', 'min:2000', 'max:2100'],
            'month'      => ['required', 'integer', 'min:1', 'max:12'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.required'           => 'El año es obligatorio.',
            'month.required'          => 'El mes es obligatorio.',
            'month.between'           => 'El mes debe estar entre 1 y 12.',
            'start_date.required'     => 'La fecha de inicio es obligatoria.',
            'end_date.required'       => 'La fecha de cierre es obligatoria.',
            'end_date.after_or_equal' => 'La fecha de cierre debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}
