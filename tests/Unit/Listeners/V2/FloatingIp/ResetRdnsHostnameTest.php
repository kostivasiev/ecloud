<?php

namespace Tests\Unit\Listeners\V2\FloatingIp;

use App\Traits\V2\Jobs\FloatingIp\RdnsTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use App\Events\V2\FloatingIp\Deleted;
use App\Jobs\FloatingIp\ResetRdnsHostname;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Tests\TestCase;
use UKFast\Admin\SafeDNS\AdminClient;
use UKFast\Admin\SafeDNS\AdminRecordClient;
use UKFast\SDK\Page;
use UKFast\SDK\SafeDNS\Entities\Record;
use UKFast\SDK\SafeDNS\RecordClient;

class ResetRdnsHostnameTest extends TestCase
{
    use RdnsTrait;

    protected Deleted $event;

    protected Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $mockRecordAdminClient = \Mockery::mock(AdminRecordClient::class);

        $mockSafednsAdminClient = \Mockery::mock(AdminClient::class);

        $mockSafednsAdminClient->shouldReceive('records')->andReturn(
            $mockRecordAdminClient
        );
        app()->bind(AdminClient::class, function () use ($mockSafednsAdminClient) {
            return $mockSafednsAdminClient;
        });

        app()->bind(AdminRecordClient::class, function () use ($mockRecordAdminClient) {
            return $mockRecordAdminClient;
        });

        $mockRecordAdminClient->shouldReceive('getPage')->andReturnUsing(function () {
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

        $mockRecordAdminClient->shouldReceive('update')->andReturnTrue();

        app()->bind(RecordClient::class, function () use ($mockRecordAdminClient) {
            return $mockRecordAdminClient;
        });
    }

    public function testResetRdnsSuccess()
    {
        $task = $this->createSyncDeleteTask($this->floatingIp());

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new ResetRdnsHostname($task));

        Event::assertNotDispatched(JobFailed::class);

        $this->floatingIp()->refresh();

        $this->assertEquals($this->floatingIp()->rdns_hostname, $this->reverseIpDefault($this->floatingIp()->ip_address));
    }

    public function testResetRdnsRecordNotFoundWarnsAndCompletes()
    {
        $task = $this->createSyncDeleteTask($this->floatingIp());

        Event::fake([JobFailed::class, JobProcessed::class]);

        $mockSafednsAdminClient = \Mockery::mock(AdminClient::class);
        $mockSafednsAdminClient->shouldReceive('records->getPage')->andReturnUsing(function () {
            $page = \Mockery::mock(Page::class)->makePartial();
            $page->shouldReceive('getItems')->andReturn([]);
            return $page;
        });
        $mockSafednsAdminClient->shouldNotReceive('records->update');

        app()->bind(AdminClient::class, function () use ($mockSafednsAdminClient) {
            return $mockSafednsAdminClient;
        });

        dispatch(new ResetRdnsHostname($task));

        Event::assertDispatched(JobProcessed::class);

        Event::assertNotDispatched(JobFailed::class);
    }
}
