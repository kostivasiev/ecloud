<?php

namespace Tests\unit\Jobs\Nsx\Dhcp;

use App\Jobs\Nsx\Dhcp\Undeploy;
use App\Jobs\Nsx\Dhcp\UndeployCheck;
use App\Models\V2\Dhcp;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployTest extends TestCase
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

        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/dhcp-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->dhcp));

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

        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/dhcp-test')
            ->andThrows(new \Exception());

        dispatch(new Undeploy($this->dhcp));
    }
}
