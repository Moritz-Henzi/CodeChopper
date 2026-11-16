<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingMatrixRowResource extends JsonResource
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
            'cells' => TrainingCellResource::collection($this->resource['cells']),
        ];
    }
}
