<?php

namespace Tests\unit\Jobs\Kingpin\Instance;

use App\Jobs\Kingpin\Instance\MoveToPublicHostGroup;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MoveToPublicHostGroupTest extends TestCase
{
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testMove()
    {
        $this->instance()->host_group_id = $this->hostGroup()->id;
        $this->instance()->saveQuietly();

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/reschedule',
                [
                    'json' => [
                        'resourceTierTags' => config('instance.resource_tier_tags')
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new MoveToPublicHostGroup($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testAlreadyInPublicHostGroupSkips()
    {
        Event::fake([JobFailed::class]);

        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')
            ->withSomeOfArgs(MoveToPublicHostGroup::class . ': Instance i-test is already in the Public host group, nothing to do');

        dispatch(new MoveToPublicHostGroup($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
