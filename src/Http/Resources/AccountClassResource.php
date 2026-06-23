<?php

namespace Numerar\Contable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountClassResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'name'        => $this->name,
            'nature'      => $this->nature->value,
            'nature_label'=> $this->nature->label(),
            'active'      => $this->active,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
