<?php

namespace Numerar\Contable\Http\Requests;

class UpdateEntryRequest extends StoreEntryRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'entry_type' => ['prohibited'],
            'lines'      => ['sometimes', 'array', 'min:2'],
        ]);
    }
}
