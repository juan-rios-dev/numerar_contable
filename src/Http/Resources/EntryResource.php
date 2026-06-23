<?php

namespace Numerar\Contable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'entry_number'     => $this->entry_number,
            'entry_type'       => $this->entry_type,
            'entry_type_label' => $this->whenLoaded('entryType', fn () => $this->entryType->name, $this->entry_type),
            'date'             => $this->date?->toDateString(),
            'description'      => $this->description,
            'status'           => $this->status->value,
            'status_label'     => $this->status->label(),
            'is_voided'        => $this->isVoided(),
            'total_debit'      => $this->whenLoaded('lines', fn () => $this->totalDebits()),
            'total_credit'     => $this->whenLoaded('lines', fn () => $this->totalCredits()),
            'is_balanced'      => $this->whenLoaded('lines', fn () => $this->isBalanced()),
            'period'           => PeriodResource::make($this->whenLoaded('period')),
            'lines'            => EntryLineResource::collection($this->whenLoaded('lines')),
            'created_at'       => $this->created_at?->toDateTimeString(),
        ];
    }
}
