<?php

namespace App\Models;

use App\Models\Concerns\FlushesTrainingMatrixCache;
use Illuminate\Database\Eloquent\Relations\Pivot;

class JobTitleUser extends Pivot
{
    use FlushesTrainingMatrixCache;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_title_user';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
