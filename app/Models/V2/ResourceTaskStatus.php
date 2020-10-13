<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Imtigger\LaravelJobStatus\JobStatus;

/**
 * App\Models\V2\ResourceTaskStatus.
 *
 * @property string $id
 * @property string $resource_id
 */
class ResourceTaskStatus extends JobStatus
{
    use CustomKey;

    public $keyPrefix = 'task';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $table = 'resource_task_statuses';
}