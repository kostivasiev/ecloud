<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Imtigger\LaravelJobStatus\JobStatus;

/**
 * App\Models\V2\TaskJobStatus.
 *
 * @property string $id
 * @property string $resource_id
 */
class Task extends Model
{
    use CustomKey;

    protected $fillable = [
        "resource_id"
    ];

    public $keyPrefix = 'task';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $table = 'tasks';


    public function jobs()
    {
        return $this->hasMany(TaskJobStatus::class, "task_id", "id");
    }
}