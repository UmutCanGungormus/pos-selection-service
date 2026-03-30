<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pos_name' => $this->pos_name,
            'card_type' => $this->card_type->value,
            'card_brand' => $this->card_brand,
            'installment' => $this->installment,
            'currency' => $this->currency->value,
            'commission_rate' => (float) $this->commission_rate,
            'min_fee' => (float) $this->min_fee,
            'priority' => $this->priority,
        ];
    }
}
