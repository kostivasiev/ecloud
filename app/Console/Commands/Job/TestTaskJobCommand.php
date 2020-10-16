<?php

namespace App\Console\Commands\Job;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;
use App\Jobs\TestTaskJob;

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
        $this->info('TestJobCommand: Dispatching job');
        dispatch(new TestTaskJob(Instance::findOrFail("i-0efdbc98")));
        $this->info('TestJobCommand: Dispatched job');
    }
}
