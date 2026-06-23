<?php

namespace Numerar\Contable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PeriodResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'year'          => $this->year,
            'month'         => $this->month,
            'name'          => $this->name,
            'start_date'    => $this->start_date?->toDateString(),
            'end_date'      => $this->end_date?->toDateString(),
            'status'        => $this->status->value,
            'status_label'  => $this->status->label(),
            'is_open'       => $this->isOpen(),
            'opened_at'     => $this->opened_at?->toDateTimeString(),
            'closed_at'     => $this->closed_at?->toDateTimeString(),
            'entries_count' => $this->whenCounted('entries'),
        ];
    }
}
