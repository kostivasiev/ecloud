<?php

namespace Tests\unit\Jobs\Kingpin\Instance;

use App\Jobs\Kingpin\Instance\MoveToPrivateHostGroup;
use App\Jobs\Kingpin\Instance\MoveToPublicHostGroup;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MoveToPrivateHostGroupTest extends TestCase
{
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testMove()
    {
        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/reschedule',
                [
                    'json' => [
                        'hostGroupId' => $this->hostGroup()->id,
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new MoveToPrivateHostGroup($this->instanceModel(), $this->hostGroup()->id));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testAlreadyInHostGroupSkips()
    {
        Event::fake([JobFailed::class]);
        $this->instanceModel()->host_group_id = $this->hostGroup()->id;
        $this->instanceModel()->saveQuietly();

        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')
            ->withSomeOfArgs(MoveToPrivateHostGroup::class . ': Instance i-test is already in the host group ' . $this->hostGroup()->id . ', nothing to do');

        dispatch(new MoveToPrivateHostGroup($this->instanceModel(), $this->hostGroup()->id));
        
        Event::assertNotDispatched(JobFailed::class);
    }
}
