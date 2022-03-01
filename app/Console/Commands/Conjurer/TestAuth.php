<?php

namespace App\Console\Commands\Conjurer;

use App\Models\V2\AvailabilityZone;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;

class TestAuth extends Command
{
    protected $signature = 'conjurer:test-auth';

    protected $description = 'Performs Conjurer auth';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /** @var  $availabilityZone
         * Conjurer credentials have been stored against this resource
         */
        //$availabilityZone = AvailabilityZone::findOrFail('az-aaaaaaaa');
        $availabilityZone = AvailabilityZone::firstOrFail();

        try {
            /** @var Response $response */
            $availabilityZone->conjurerService()->get('/api/v2/compute/GC-UCS-FI2-DEV-A/vpc/vpc-abcdef12/node/asdasd');
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
