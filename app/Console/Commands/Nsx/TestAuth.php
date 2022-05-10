<?php

namespace App\Console\Commands\Nsx;

use App\Models\V2\AvailabilityZone;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use App\Console\Commands\Command;

class TestAuth extends Command
{
    protected $signature = 'nsx:test-auth';

    protected $description = 'Performs auth against the configured NSX instance';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $availabilityZone = AvailabilityZone::firstOrFail();
            /** @var Response $response */
            $availabilityZone->nsxService()->get('policy/api/v1');
        } catch (RequestException $exception) {
            if ($exception->getCode() == 404) {
                $this->info('Auth test passed');
                return Command::SUCCESS;
            }
        }

        $this->error('Auth test failed');
        return Command::FAILURE;
    }
}
