<?php

namespace App\Models\Concerns;

use App\Services\TrainingStatusService;

/**
 * Forgets today's cached dashboard matrix whenever the model is saved or deleted.
 */
trait FlushesTrainingMatrixCache
{
    /**
     * Register the model events that flush the cached matrix.
     */
    protected static function bootFlushesTrainingMatrixCache(): void
    {
        static::saved(fn () => app(TrainingStatusService::class)->forgetCachedMatrix());
        static::deleted(fn () => app(TrainingStatusService::class)->forgetCachedMatrix());
    }
}
