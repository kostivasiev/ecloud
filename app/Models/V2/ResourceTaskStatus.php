<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
/**
 * App\Models\V2\ResourceTaskStatus.
 *
 * @property string    $resource_id
*/
class ResourceTaskStatus extends \Imtigger\LaravelJobStatus\JobStatus {
    use CustomKey;

    public $keyPrefix = 'task';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $table = 'resource_task_statuses';
    public $incrementing = false;
}