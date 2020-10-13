<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Imtigger\LaravelJobStatus\JobStatus;

/**
 * App\Models\V2\ResourceTask.
 *
 * @property string $id
 * @property string $resource_id
 */
class ResourceTask extends JobStatus
{
    use CustomKey;

    public $keyPrefix = 'task';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $table = 'resource_tasks';
}