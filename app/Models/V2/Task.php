<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\V2\Task.
 *
 * @property string $id
 * @property string $resource_id
 */
class Task extends Model
{
    use CustomKey;

    public $keyPrefix = 'task';
    public $incrementing = false;
    protected $fillable = [
        'resource_id'
    ];
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $table = 'tasks';

    public function getIsFailedAttribute()
    {
        return $this->jobStatuses()->get()->filter(function ($status) {
                return $status->is_failed;
            })->count() > 0;
    }

    public function jobStatuses()
    {
        return $this->hasMany(TaskJobStatus::class, 'task_id', 'id');
    }

    public function getIsEndedAttribute()
    {
        return $this->jobStatuses()->count() == 0 || $this->jobStatuses()->get()->filter(function ($status) {
                return !$status->is_ended;
            })->count() < 1;
    }
}
