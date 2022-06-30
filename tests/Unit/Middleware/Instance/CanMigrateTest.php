<?php

namespace Tests\Unit\Middleware\Instance;

use App\Http\Middleware\Instance\CanMigrate;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use Illuminate\Http\Request;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CanMigrateTest extends TestCase
{
    protected CanMigrate $middleware;
    protected Request $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(false));
        $this->middleware = new CanMigrate();
        $this->request = $this->createMock(Request::class);
        $this->request->expects($this->atLeastOnce())->method('route')->willReturn($this->instanceModel()->id);
    }

    public function testCanNotMigrateIfInstanceHasAffinityRule()
    {
        AffinityRuleMember::factory()
            ->for(AffinityRule::factory()
                ->create([
                    'vpc_id' => $this->vpc(),
                    'availability_zone_id' => $this->availabilityZone(),
                ]))
            ->create([
                'instance_id' => $this->instanceModel()->id,
            ]);

        $response = $this->middleware->handle($this->request, function () {});

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testCanMigrateIfInstanceHasNoAffinityRule()
    {
        $response = $this->middleware->handle($this->request, function () {});

        $this->assertNull($response);
    }
}
