<?php

namespace Numerar\Contable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EntryLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'account_id'       => $this->account_id,
            'account'          => AccountResource::make($this->whenLoaded('account')),
            'description'      => $this->description,
            'debit'            => (float) $this->debit,
            'credit'           => (float) $this->credit,
            'is_debit'         => $this->isDebit(),
            'amount'           => $this->amount(),
            'third_party_type' => $this->third_party_type,
            'third_party_id'   => $this->third_party_id,
            'cost_center_id'   => $this->cost_center_id,
            'cost_center'      => CostCenterResource::make($this->whenLoaded('costCenter')),
        ];
    }
}
