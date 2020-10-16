<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Imtigger\LaravelJobStatus\JobStatus;

/**
 * App\Models\V2\Task.
 *
 * @property string $id
 * @property string $resource_id
 */
class Task extends JobStatus
{
    use CustomKey;

    public $keyPrefix = 'task';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $table = 'tasks';

    const TASK_FINISHED_STATUSES = [self::STATUS_FINISHED, self::STATUS_FAILED];
}