<?php

namespace App\Models;

use App\Models\Concerns\FlushesTrainingMatrixCache;
use Database\Factories\JobTitleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class JobTitle extends Model
{
    /** @use HasFactory<JobTitleFactory> */
    use FlushesTrainingMatrixCache, HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the users that hold this job title.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(JobTitleUser::class);
    }

    /**
     * Get the trainings required for this job title.
     *
     * @return BelongsToMany<Training, $this>
     */
    public function trainings(): BelongsToMany
    {
        return $this->belongsToMany(Training::class)->using(JobTitleTraining::class);
    }
}
