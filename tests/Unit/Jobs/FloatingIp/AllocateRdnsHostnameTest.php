<?php

namespace Tests\Unit\Jobs\FloatingIp;

use App\Jobs\FloatingIp\AllocateRdnsHostname;
use App\Traits\V2\Jobs\FloatingIp\RdnsTrait;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\SafeDNS\AdminClient;
use UKFast\Admin\SafeDNS\AdminRecordClient;
use UKFast\SDK\SafeDNS\Entities\Record;

class AllocateRdnsHostnameTest extends TestCase
{
    use RdnsTrait;

    protected $mockRecordAdminClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockRecordAdminClient = \Mockery::mock(AdminRecordClient::class);

        $mockSafednsAdminClient = \Mockery::mock(AdminClient::class);

        $mockSafednsAdminClient->shouldReceive('records')->andReturn(
            $this->mockRecordAdminClient
        );
        app()->bind(AdminClient::class, function () use ($mockSafednsAdminClient) {
            return $mockSafednsAdminClient;
        });

        app()->bind(AdminRecordClient::class, function () {
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
                            "content" => "198.172.168.0.svrlist.co.uk",
                            "updated_at" => "1970-01-01T01:00:00+01:00",
                            "ttl" => 86400,
                            "priority" => null
                        ]
                    )
                ])
            );

            return $mockRecord;
        });

        $this->mockRecordAdminClient->expects('update')->andReturnTrue();
    }

    public function testRdnsAllocated()
    {
        $ip = '10.0.0.1';
        $this->floatingIp()->setAttribute('ip_address', $ip)->saveQuietly();
        $task = $this->createSyncUpdateTask($this->floatingIp());

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AllocateRdnsHostname($task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->floatingIp()->refresh();

        $this->assertEquals($this->floatingIp()->rdns_hostname, $this->reverseIpDefault($ip));
    }
}
