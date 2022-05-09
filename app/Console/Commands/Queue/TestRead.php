<?php

namespace App\Console\Commands\Queue;

use App\Console\Commands\Command;
use Illuminate\Support\Facades\Redis;

class TestRead extends Command
{
    protected $signature = 'queue:test-read';

    protected $description = 'Test read from queue';

    public function handle()
    {
        for ($i = 0; $i < Redis::llen('queues:default'); $i++) {
            var_dump(json_decode(Redis::lindex('queues:default', $i), JSON_PRETTY_PRINT));
            //var_dump(unserialize(json_decode(Redis::lindex('queues:default', $i), JSON_PRETTY_PRINT)['data']['command']));
        }

        return Command::SUCCESS;
    }
}
