<?php

namespace App\Console\Commands\Kingpin;

use App\Models\V2\AvailabilityZone;
use GuzzleHttp\Psr7\Response;
use App\Console\Commands\Command;

class TestAuth extends Command
{
    protected $signature = 'kingpin:test-auth';

    protected $description = 'Performs Kingpin auth';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /** @var  $availabilityZone
         * Kingpin credentials have been stored against this resource
         */
        //$availabilityZone = AvailabilityZone::findOrFail('avz-2b66bb79');
        $availabilityZone = AvailabilityZone::firstOrFail();

        try {
            /** @var Response $response */
            $availabilityZone->kingpinService()->get('/api/v1/application/version');
        } catch (\Exception $exception) {
            if ($exception->getCode() == 401) {
                $this->error('Auth test failed');
                return Command::FAILURE;
            }
        }
        $this->info('Auth test passed');

        return Command::SUCCESS;
    }
}
