<?php

namespace App\Services;

use App\Enums\TrainingStatus;
use App\Models\JobTitle;
use App\Models\Training;
use App\Models\TrainingCompletion;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the dashboard matrix of crew members × trainings.
 */
class TrainingStatusService
{
    /**
     * Get the cache key of the dashboard matrix for the given day.
     */
    public function matrixCacheKey(?CarbonInterface $date = null): string
    {
        return 'dashboard:matrix:'.($date ?? today())->toDateString();
    }

    /**
     * Forget today's cached dashboard matrix.
     */
    public function forgetCachedMatrix(): void
    {
        Cache::forget($this->matrixCacheKey());
    }

    /**
     * Build the matrix of all crew members with at least one job title.
     *
     * Each row only contains cells for trainings required by the user's job titles.
     */
    public function buildMatrix(?CarbonInterface $today = null): array
    {
        $today = ($today ?? today())->copy()->startOfDay();

        $trainings = Training::query()->orderBy('name')->get(['id', 'name']);

        $users = User::query()
            ->whereHas('jobTitles')
            ->with(['jobTitles' => fn ($query) => $query->orderBy('name'), 'jobTitles.trainings:id', 'trainingCompletions'])
            ->orderBy('name')
            ->get();

        return [
            'trainings' => $trainings,
            'rows' => $users->map(fn (User $user): array => $this->buildRow($user, $trainings, $today))->values(),
        ];
    }

    /**
     * Build the training status of a single crew member.
     *
     * Only trainings required by the user's job titles are included.
     */
    public function buildForCrewMember(User $user, ?CarbonInterface $today = null): array
    {
        $today = ($today ?? today())->copy()->startOfDay();

        $user->load(['jobTitles' => fn ($query) => $query->orderBy('name'), 'jobTitles.trainings:id', 'trainingCompletions']);

        $trainings = Training::query()
            ->whereHas('jobTitles.users', fn ($query) => $query->whereKey($user->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'trainings' => $trainings,
            ...$this->buildRow($user, $trainings, $today),
        ];
    }

    /**
     * Determine the status of a training based on the user's latest completion.
     *
     * A required training that was never completed is red.
     */
    public function determineStatus(?TrainingCompletion $completion, CarbonInterface $today): TrainingStatus
    {
        if ($completion === null) {
            return TrainingStatus::Red;
        }

        if ($completion->valid_until === null) {
            return TrainingStatus::Green;
        }

        if ($today->greaterThan($completion->valid_until)) {
            return TrainingStatus::Red;
        }

        if ($completion->renewal_from !== null && $today->greaterThanOrEqualTo($completion->renewal_from)) {
            return TrainingStatus::Yellow;
        }

        return TrainingStatus::Green;
    }

    /**
     * Build a single matrix row for the given user.
     *
     * @param  Collection<int, Training>  $trainings
     */
    private function buildRow(User $user, Collection $trainings, CarbonInterface $today): array
    {
        $requiredTrainingIds = $user->jobTitles
            ->flatMap(fn (JobTitle $jobTitle) => $jobTitle->trainings->pluck('id'))
            ->unique();

        $latestCompletions = $user->trainingCompletions
            ->sortByDesc(fn (TrainingCompletion $completion): string => $completion->completed_at->toDateString())
            ->unique('training_id')
            ->keyBy('training_id');

        return [
            'crew_member' => $user,
            'cells' => $trainings
                ->filter(fn (Training $training): bool => $requiredTrainingIds->contains($training->id))
                ->map(fn (Training $training): array => $this->buildCell($training->id, $latestCompletions->get($training->id), $today))
                ->values(),
        ];
    }

    /**
     * Build the cell of one training for one crew member.
     */
    private function buildCell(int $trainingId, ?TrainingCompletion $completion, CarbonInterface $today): array
    {
        return [
            'training_id' => $trainingId,
            'status' => $this->determineStatus($completion, $today),
            'days_left' => $completion?->valid_until === null
                ? null
                : (int) $today->diffInDays($completion->valid_until, false),
            'latest_completion' => $completion,
        ];
    }
}
