<?php

namespace Tests\unit\Router;

use App\Jobs\Instance\PowerOff;
use App\Listeners\V2\Router\DefaultRouterThroughput;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use App\Models\V2\Vpc;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PowerOffTests extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testPowerOffJob()
    {
        // OsCustomisation :: Connect NIC to network
        $this->kingpinServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id . '/power')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(true));
            });

        $job = new PowerOff();
        $job->handle();
    }
}
