<?php

namespace Tests\unit\Jobs\Vip;

use App\Events\V2\Task\Created;
use App\Jobs\Tasks\Nic\AssociateIp;
use App\Jobs\Vip\AssignToNics;
use App\Models\V2\Instance;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Support\Sync;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class AssignToNicsTest extends TestCase
{
    use VipMock;

    protected \Faker\Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testAssignIpAddress()
    {
        Event::fake([JobFailed::class, Created::class]);

        factory(Instance::class, 2)->create([
            'vpc_id' => $this->vpc()->id,
            'name' => 'Load Balancer Instance ' . uniqid(),
            'image_id' => $this->image()->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'platform' => 'Linux',
            'availability_zone_id' => $this->availabilityZone()->id,
            'load_balancer_id' => $this->loadBalancer()->id
        ])->each(function ($instance) {
            factory(Nic::class)->create([
                'mac_address' => $this->faker->macAddress,
                'instance_id' => $instance->id,
                'network_id' => $this->network()->id,
            ]);
        });

        $this->vip()->assignClusterIp();

        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vip());
            $task->save();
            return $task;
        });

        $nic = $this->loadBalancer()->instances()->first()->nics()->first();

        // Bind and return completed ID on creation
        app()->bind(Task::class, function () use ($nic) {
            $task = new Task([
                'completed' => true,
                'name' => AssociateIp::$name
            ]);
            $task->resource()->associate($nic);
            return $task;
        });

        dispatch(new AssignToNics($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == AssociateIp::$name;
        });

        $task->refresh();

        $this->assertNotNull($task->data['task.' . AssociateIp::$name . '.ids']);
    }

    public function testIpAddressAlreadyAssignedSkips()
    {
        Event::fake([JobFailed::class]);

        factory(Instance::class)->create([
            'vpc_id' => $this->vpc()->id,
            'name' => 'Load Balancer Instance ' . uniqid(),
            'image_id' => $this->image()->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'platform' => 'Linux',
            'availability_zone_id' => $this->availabilityZone()->id,
            'load_balancer_id' => $this->loadBalancer()->id
        ])->each(function ($instance) {
            factory(Nic::class)->create([
                'mac_address' => $this->faker->macAddress,
                'instance_id' => $instance->id,
                'network_id' => $this->network()->id,
            ]);
        });

        $ipAddress = $this->vip()->assignClusterIp();

        $nic = $this->loadBalancer()->instances()->first()->nics()->first();

        $nic->ipAddresses()->save($ipAddress);

        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vip());
            $task->save();
            return $task;
        });

        dispatch(new AssignToNics($task));

        $task->refresh();

        $this->assertNull($task->data);
    }
}