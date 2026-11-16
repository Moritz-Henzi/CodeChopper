<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrainingDetailResource;
use App\Models\Training;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainingController extends Controller
{
    /**
     * Return all trainings.
     */
    public function index(): AnonymousResourceCollection
    {
        return TrainingDetailResource::collection(Training::query()->orderBy('name')->get());
    }

    /**
     * Return a single training.
     */
    public function show(Training $training): TrainingDetailResource
    {
        return new TrainingDetailResource($training);
    }
}
