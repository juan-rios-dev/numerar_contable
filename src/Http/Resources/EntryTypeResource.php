<?php

namespace Numerar\Contable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EntryTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'code'       => $this->code,
            'name'       => $this->name,
            'is_closing' => $this->is_closing,
            'is_system'  => $this->is_system,
            'active'     => $this->active,
            'sequences'  => EntrySequenceResource::collection($this->whenLoaded('sequences')),
        ];
    }
}
