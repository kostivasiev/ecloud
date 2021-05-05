<?php

namespace Tests\unit\Jobs\Nsx\Dhcp;

use App\Events\V2\Nic\Saved;
use App\Events\V2\Nic\Saving;
use App\Jobs\Instance\Deploy\ConfigureNics;
use App\Jobs\Kingpin\Volume\Undeploy;
use App\Jobs\Nsx\Dhcp\Create;
use App\Models\V2\Dhcp;
use App\Models\V2\Nic;
use App\Models\V2\Volume;
use App\Rules\V2\IpAvailable;
use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $dhcp;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSucceeds()
    {
        Model::withoutEvents(function() {
            $this->dhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        $this->nsxServiceMock()->expects('put')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/dhcp-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new Create($this->dhcp));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFails()
    {
        Volume::withoutEvents(function() {
            $this->dhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        $this->expectException(\Exception::class);

        $this->nsxServiceMock()->expects('put')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/dhcp-test')
            ->andThrows(new \Exception());

        dispatch(new Create($this->dhcp));
    }
}
