<?php

namespace App\Console\Commands\Artisan;

use App\Models\V2\AvailabilityZone;
use GuzzleHttp\Psr7\Response;
use App\Console\Commands\Command;

class TestAuth extends Command
{
    protected $signature = 'artisan:test-auth';

    protected $description = 'Performs Artisan auth';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        /** @var  $availabilityZone
         * Artisan credentials have been stored against this resource
         */
        //$availabilityZone = AvailabilityZone::findOrFail('az-aaaaaaaa');
        $availabilityZone = AvailabilityZone::firstOrFail();

        try {
            /** @var Response $response */
            $availabilityZone->artisanService()->get('/api/v2/san/' . $availabilityZone->san_name .'/freespace');
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
