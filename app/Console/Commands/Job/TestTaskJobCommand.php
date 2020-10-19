<?php

namespace App\Console\Commands\Job;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;
use App\Jobs\TestTaskChildJob;

class TestTaskJobCommand extends Command
{
    protected $signature = 'job:testtaskjob';

    protected $description = 'Executes a test job';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $task = Task::create([
            'resource_id' => "i-abcdef12"
        ]);

        $this->info('TestJobCommand: Dispatching job');
        dispatch(new TestTaskChildJob($task));
        $this->info('TestJobCommand: Dispatched job');
    }
}
