<?php

namespace Numerar\Contable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EntrySequenceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'entry_type_id'  => $this->entry_type_id,
            'name'           => $this->name,
            'prefix'         => $this->prefix,
            'initial_number' => $this->initial_number,
            'priority'       => $this->priority,
            'active'         => $this->active,
        ];
    }
}
