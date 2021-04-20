<?php

namespace Tests\unit\Jobs\Nsx\Dhcp;

use App\Jobs\Nsx\Dhcp\Create;
use App\Jobs\Nsx\Dhcp\Undeploy;
use App\Jobs\Nsx\Dhcp\UndeployCheck;
use App\Models\V2\Dhcp;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployCheckTest extends TestCase
{
    use DatabaseMigrations;

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

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [],
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new UndeployCheck($this->dhcp));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenStillExists()
    {
        Model::withoutEvents(function() {
            $this->dhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'dhcp-test'
                        ],
                    ],
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new UndeployCheck($this->dhcp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
