<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrewMemberTrainingStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'crew_member' => new CrewMemberResource($this->resource['crew_member']),
            'trainings' => TrainingResource::collection($this->resource['trainings']),
            'cells' => TrainingCellResource::collection($this->resource['cells']),
        ];
    }
}
