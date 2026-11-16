<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingCellResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{training_id: int, status: string, days_left: ?int, completed_at: ?string, renewal_from: ?string, valid_until: ?string}
     */
    public function toArray(Request $request): array
    {
        $completion = $this->resource['latest_completion'];

        return [
            'training_id' => $this->resource['training_id'],
            'status' => $this->resource['status']->value,
            'days_left' => $this->resource['days_left'],
            'completed_at' => $completion?->completed_at?->toDateString(),
            'renewal_from' => $completion?->renewal_from?->toDateString(),
            'valid_until' => $completion?->valid_until?->toDateString(),
        ];
    }
}
