<?php

namespace Tests\V2\Instances;

use App\Models\V2\ImageMetadata;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $task = app()->make(Task::class);
        $task->id = 't-test';
        app()->instance(Task::class, $task);
    }

    public function testValidDataIsSuccessful()
    {
        Event::fake();
        $this->patch(
            '/v2/instances/' . $this->instanceModel()->id,
            [
                'name' => 'Changed',
                'backup_enabled' => true,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);

        $this->assertDatabaseHas(
            'instances',
            [
                'id' => $this->instanceModel()->id,
                'name' => 'Changed'
            ],
            'ecloud'
        );

        $this->instanceModel()->refresh();
        $this->assertEquals('Changed', $this->instanceModel()->name);
        $this->assertTrue($this->instanceModel()->backup_enabled);
    }

    public function testAdminCanModifyLockedInstance()
    {
        Event::fake();

        // Lock the instance
        $this->instanceModel()->locked = true;
        $this->instanceModel()->save();

        $data = [
            'name' => 'Changed',
        ];
        $this->patch(
            '/v2/instances/' . $this->instanceModel()->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);

        $this->assertDatabaseHas(
            'instances',
            [
                'id' => $this->instanceModel()->id,
                'name' => 'Changed'
            ],
            'ecloud'
        );
    }

    public function testScopedAdminCanNotModifyLockedInstance()
    {
        Event::fake();

        $this->instanceModel()->locked = true;
        $this->instanceModel()->save();
        $this->patch(
            '/v2/instances/' . $this->instanceModel()->id,
            [
                'name' => 'Testing Locked Instance',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => '1',
            ]
        )->assertJsonFragment([
            'title' => 'Forbidden',
            'detail' => 'The specified Instance is locked',
            'status' => 403,
        ])->assertStatus(403);
    }

    public function testLockedInstanceIsNotEditable()
    {
        Event::fake();

        // Lock the instance
        $this->instanceModel()->locked = true;
        $this->instanceModel()->save();
        $this->patch(
            '/v2/instances/' . $this->instanceModel()->id,
            [
                'name' => 'Testing Locked Instance',
            ],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment([
            'title' => 'Forbidden',
            'detail' => 'The specified Instance is locked',
            'status' => 403,
        ])->assertStatus(403);

        // Unlock the instance
        $this->instanceModel()->locked = false;
        $this->instanceModel()->saveQuietly();

        $data = [
            'name' => 'Changed',
        ];
        $this->patch(
            '/v2/instances/' . $this->instanceModel()->id,
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);

        $this->assertDatabaseHas(
            'instances',
            [
                'id' => $this->instanceModel()->id,
                'name' => 'Changed'
            ],
            'ecloud'
        );
    }

    public function testApplianceSpecRamMax()
    {
        ImageMetadata::factory()->create([
            'key' => 'ukfast.spec.ram.max',
            'value' => 2048,
            'image_id' => $this->image()->id,
        ]);

        $data = [
            'ram_capacity' => 3072,
        ];

        $this->patch(
            '/v2/instances/' . $this->instanceModel()->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'Specified ram capacity is above the maximum of 2048',
            'status' => 422,
            'source' => 'ram_capacity'
        ])->assertStatus(422);
    }

    public function testApplianceSpecVcpuMax()
    {
        ImageMetadata::factory()->create([
            'key' => 'ukfast.spec.cpu_cores.max',
            'value' => 5,
            'image_id' => $this->image()->id,
        ]);

        $data = [
            'vcpu_cores' => 6,
        ];

        $this->patch(
            '/v2/instances/' . $this->instanceModel()->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'Specified vcpu cores is above the maximum of 5',
            'status' => 422,
            'source' => 'vcpu_cores'
        ])->assertStatus(422);
    }
}
