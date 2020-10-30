<?php

namespace App\Events\V2\FirewallPolicy;

use App\Models\V2\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Saved
{
    use SerializesModels;

    public $task;
    public $model;

    public function __construct(Task $task, Model $model)
    {
        $this->task = $task;
        $this->model = $model;
    }
}
