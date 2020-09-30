<?php

namespace App\Console\Commands\Nsx;

use App\Services\V2\NsxService;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;

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
            /** @var Response $response */
            app()->make(NsxService::class)->get('policy/api/v1');
        } catch (RequestException $exception) {
            if ($exception->getCode() == 404) {
                $this->info('Auth test passed');
                return;
            }
        }
        $this->error('Auth test failed');
    }
}
