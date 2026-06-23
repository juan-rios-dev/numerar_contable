<?php

namespace Numerar\Contable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FiscalYearResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'year'              => $this->year,
            'status'            => $this->status,
            'is_open'           => $this->isOpen(),
            'is_closed'         => $this->isClosed(),
            'closing_entry_id'  => $this->closing_entry_id,
            'closing_entry'     => $this->whenLoaded('closingEntry', fn () => [
                'id'           => $this->closingEntry->id,
                'entry_number' => $this->closingEntry->entry_number,
            ]),
            'opened_at'         => $this->opened_at?->toDateTimeString(),
            'closed_at'         => $this->closed_at?->toDateTimeString(),
        ];
    }
}
