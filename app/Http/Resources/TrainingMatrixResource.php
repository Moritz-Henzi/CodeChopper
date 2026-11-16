<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingMatrixResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'trainings' => TrainingResource::collection($this->resource['trainings']),
            'rows' => TrainingMatrixRowResource::collection($this->resource['rows']),
        ];
    }
}
