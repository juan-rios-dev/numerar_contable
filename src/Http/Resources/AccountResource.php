<?php

namespace Numerar\Contable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'parent_id'            => $this->parent_id,
            'class_id'             => $this->class_id,
            'class_name'           => $this->whenLoaded('class', fn () => $this->class->name),
            'code'                 => $this->code,
            'name'                 => $this->name,
            'full_name'            => $this->full_name,
            'description'          => $this->description,
            'nature'               => $this->nature->value,
            'nature_label'         => $this->nature->label(),
            'account_type'         => $this->account_type->value,
            'account_type_label'   => $this->account_type->label(),
            'active'               => $this->active,
            'children'             => AccountResource::collection($this->whenLoaded('children')),
        ];
    }
}
