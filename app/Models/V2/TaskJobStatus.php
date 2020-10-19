<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Imtigger\LaravelJobStatus\JobStatus;

/**
 * App\Models\V2\TaskJobStatus.
 *
 * @property string $id
 * @property string $task_id
 */
class TaskJobStatus extends JobStatus
{
    use CustomKey;

    public $keyPrefix = 'job';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $table = 'task_jobs';

    const TASK_FINISHED_STATUSES = [self::STATUS_FINISHED, self::STATUS_FAILED];
}