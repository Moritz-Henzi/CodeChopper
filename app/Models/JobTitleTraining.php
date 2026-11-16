<?php

namespace App\Models;

use App\Models\Concerns\FlushesTrainingMatrixCache;
use Illuminate\Database\Eloquent\Relations\Pivot;

class JobTitleTraining extends Pivot
{
    use FlushesTrainingMatrixCache;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_title_training';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
