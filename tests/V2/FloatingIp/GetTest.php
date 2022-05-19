<?php

namespace Tests\V2\FloatingIp;

use App\Models\V2\IpAddress;
use App\Models\V2\Task;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->floatingIp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testIndex()
    {
        $this->get('/v2/floating-ips')
            ->assertJsonFragment([
                'id' => 'fip-test',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '1.1.1.1'
            ])
            ->assertStatus(200);
    }

    public function testShow()
    {
        $ipAddress = IpAddress::factory()->create();
        $this->assignFloatingIp($this->floatingIp(), $ipAddress);

        $this->get('/v2/floating-ips/' . $this->floatingIp()->id)
            ->assertJsonFragment([
                'id' => 'fip-test',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '1.1.1.1',
                'resource_id' => $ipAddress->id
            ])
            ->assertStatus(200);
    }

    public function testSyncStatusAssignFloatingIpTaskInProgress()
    {
        $task = new Task([
            'id' => 'task-1',
            'completed' => false,
            'name' => 'floating_ip_assign',
        ]);
        $task->resource()->associate($this->floatingIp());
        $task->save();

        $this->get('/v2/floating-ips/' . $this->floatingIp()->id)
            ->assertJsonFragment([
                'id' => 'fip-test',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '1.1.1.1',
                'sync' => [
                    'status' => 'in-progress',
                    'type' =>  'floating_ip_assign'
                ]
            ])
            ->assertStatus(200);
    }

    public function testSyncStatusAssignFloatingIpTaskCompleted()
    {
        $task = new Task([
            'id' => 'task-1',
            'completed' => true,
            'name' => 'floating_ip_assign',
        ]);
        $task->resource()->associate($this->floatingIp());
        $task->save();

        $this->get('/v2/floating-ips/' . $this->floatingIp()->id)
            ->assertJsonFragment([
                'id' => 'fip-test',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '1.1.1.1',
                'sync' => [
                    'status' => 'complete',
                    'type' =>  'floating_ip_assign'
                ]
            ])->assertStatus(200);
    }
}
