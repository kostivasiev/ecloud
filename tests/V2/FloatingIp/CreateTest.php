<?php

namespace Tests\V2\FloatingIp;

use App\Support\Sync;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testSuccess()
    {
        Event::fake([\App\Events\V2\Task\Created::class]);

        $data = [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id
        ];

        $this->post('/v2/floating-ips', $data)
            ->assertStatus(202);

        $this->assertDatabaseHas(
            'floating_ips',
            $data,
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });
    }

    public function testInvalidAzIsFailed()
    {
        //TODO: Fix this test
        $this->markTestSkipped();
        $this->vpc()->setAttribute('region_id', 'test-fail')->saveQuietly();

        $data = [
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ];

        $this->post('/v2/floating-ips', $data)->seeJson([
            'title' => 'Not Found',
            'detail' => 'The specified availability zone is not available to that VPC',
            'status' => 404,
            'source' => 'availability_zone_id'
        ])->assertStatus(404);
    }
}
