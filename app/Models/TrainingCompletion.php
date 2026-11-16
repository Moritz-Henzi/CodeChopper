<?php

namespace App\Models;

use App\Models\Concerns\FlushesTrainingMatrixCache;
use Database\Factories\TrainingCompletionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'training_id', 'completed_at', 'valid_until', 'renewal_from'])]
class TrainingCompletion extends Model
{
    /** @use HasFactory<TrainingCompletionFactory> */
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
            'completed_at' => 'date',
            'valid_until' => 'date',
            'renewal_from' => 'date',
        ];
    }

    /**
     * Get the user who completed the training.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the completed training.
     *
     * @return BelongsTo<Training, $this>
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }
}
