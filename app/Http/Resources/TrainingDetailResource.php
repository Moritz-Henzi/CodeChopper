<?php

namespace App\Http\Resources;

use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Training
 */
class TrainingDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, name: string, validity_months: int|null, renewal_months: int|null, end_of_month_expiry: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'validity_months' => $this->validity_months,
            'renewal_months' => $this->renewal_months,
            'end_of_month_expiry' => $this->end_of_month_expiry,
        ];
    }
}
