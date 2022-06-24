<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\AssignSharedHostGroup;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AssignSharedHostGroupTest extends TestCase
{
    public function testNoHostGroupAvailable()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AssignSharedHostGroup($this->instanceModel()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testSuccessful()
    {
        $this->kingpinServiceMock()
            ->allows('get')
            ->with(
                sprintf(KingpinService::GET_CAPACITY_URI, $this->vpc()->id, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupId' => $this->hostGroup()->id,
                    'cpuUsedMHz' => 50,
                    'cpuCapacityMHz' => 100,
                    'ramUsedMB' => 50,
                    'ramCapacityMB' => 100,
                ]));
            });
        $this->resourceTier()->hostGroups()->attach($this->hostGroup());

        dispatch(new AssignSharedHostGroup($this->instanceModel()));

        $this->instanceModel()->refresh();
        $this->assertEquals($this->hostGroup()->id, $this->instanceModel()->deploy_data['hostGroupId']);
    }
}
