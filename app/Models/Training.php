<?php

namespace App\Models;

use App\Models\Concerns\FlushesTrainingMatrixCache;
use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'validity_months', 'renewal_months', 'end_of_month_expiry'])]
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use FlushesTrainingMatrixCache, HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'validity_months' => 'integer',
            'renewal_months' => 'integer',
            'end_of_month_expiry' => 'boolean',
        ];
    }

    /**
     * Get the job titles that require this training.
     *
     * @return BelongsToMany<JobTitle, $this>
     */
    public function jobTitles(): BelongsToMany
    {
        return $this->belongsToMany(JobTitle::class)->using(JobTitleTraining::class);
    }

    /**
     * Get the completions recorded for this training.
     *
     * @return HasMany<TrainingCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(TrainingCompletion::class);
    }

    /**
     * Determine whether the training never expires.
     */
    public function neverExpires(): bool
    {
        return $this->validity_months === null;
    }
}
