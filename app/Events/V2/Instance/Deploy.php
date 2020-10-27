<?php

namespace App\Events\V2\Instance;

use App\Events\V2\Instance\Deploy\Data;
use App\Models\V2\Task;
use Illuminate\Queue\SerializesModels;

class Deploy
{
    use SerializesModels;

    /**
     * @var Data
     */
    public $data;
    public $task;

    public function __construct(Task $task, Data $data)
    {
        $this->task = $task;
        $this->data = $data;
    }
}
