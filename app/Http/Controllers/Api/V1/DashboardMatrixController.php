<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CrewMemberTrainingStatusResource;
use App\Http\Resources\TrainingMatrixResource;
use App\Models\User;
use App\Services\TrainingStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardMatrixController extends Controller
{
    /**
     * Return the training status matrix of all crew members.
     *
     * The rendered JSON is cached until the end of the day as a plain array,
     * because the cache store does not unserialize objects.
     */
    public function __invoke(Request $request, TrainingStatusService $trainingStatusService): JsonResponse
    {
        $matrix = Cache::remember(
            $trainingStatusService->matrixCacheKey(),
            now()->endOfDay(),
            fn (): array => (new TrainingMatrixResource($trainingStatusService->buildMatrix()))
                ->response($request)
                ->getData(true),
        );

        return response()->json($matrix);
    }

    /**
     * Return the training status of a single crew member.
     */
    public function show(User $user, TrainingStatusService $trainingStatusService): CrewMemberTrainingStatusResource
    {
        return new CrewMemberTrainingStatusResource($trainingStatusService->buildForCrewMember($user));
    }
}
