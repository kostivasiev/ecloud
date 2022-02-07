<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Jobs\FloatingIp\AllocateRdnsHostname;
use App\Models\V2\FloatingIp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\SDK\SafeDNS\Entities\Record;
use UKFast\SDK\SafeDNS\RecordClient;

class AllocateRdnsTest extends TestCase
{
    protected FloatingIp $floatingIp;
    protected $mockRecordAdminClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockRecordAdminClient = \Mockery::mock(RecordClient::class);

        app()->bind(RecordClient::class, function () {
            return $this->mockRecordAdminClient;
        });

        $this->mockRecordAdminClient->shouldReceive('getPage')->andReturnUsing(function () {
            $mockRecord = \Mockery::mock(Record::class);
            $mockRecord->shouldReceive('totalPages')->andReturn(1);
            $mockRecord->shouldReceive('getItems')->andReturn(
                new Collection([
                    new \UKFast\SDK\SafeDNS\Entities\Record(
                        [
                            "id" => 10015521,
                            "zone" => "1.2.3.in-addr.arpa",
                            "name" => "1.2.3.4.in-addr.arpa",
                            "type" => "PTR",
                            "content" => config('defaults.floating-ip.rdns.default_hostname'),
                            "updated_at" => "1970-01-01T01:00:00+01:00",
                            "ttl" => 86400,
                            "priority" => null
                        ]
                    )
                ])
            );

            return $mockRecord;
        });
    }

    public function testRdnsAllocated()
    {
        Model::withoutEvents(function () {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'ip_address' => '10.0.0.1',
            ]);
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AllocateRdnsHostname($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->floatingIp->refresh();

        $this->assertEquals($this->floatingIp->rdns_hostname, config('defaults.floating-ip.rdns.default_hostname'));
    }
}
