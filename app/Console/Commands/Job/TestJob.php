<?php

namespace App\Console\Commands\Job;

use App\Models\V2\AvailabilityZone;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;
use App\Jobs\TestTaskJob;

class TestJob extends Command
{
    protected $signature = 'job:test';

    protected $description = 'Executes a test job';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('TestJobCommand: Dispatching job');
        dispatch(new TestTaskJob("i-abcdef12"));
        $this->info('TestJobCommand: Dispatched job');
    }
}
