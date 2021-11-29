<?php

namespace App\Tasks\ExampleTask;

use App\Jobs\ExampleTask\ExampleTaskJobOne;
use App\Tasks\Task;

class ExampleTask extends Task
{
    public function jobs()
    {
        return [
            ExampleTaskJobOne::class
        ];
    }
}