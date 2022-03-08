<?php
namespace Tests\V2\Host;

use App\Events\V2\Task\Created;
use App\Models\V2\Instance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;
use App\Models\V2\Host;

class DeleteTest extends TestCase
{
    protected Collection $hosts;
    protected Collection $instances;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->hostSpec()
            ->setAttribute('cpu_cores', 2)
            ->setAttribute('ram_capacity', 2)
            ->save();
        $this->hosts = Model::withoutEvents(function () {
            $hostCount = 1;
            return Host::factory(2)->make([
                'host_group_id' => $this->hostGroup()->id,
            ])->each(function ($host) use (&$hostCount) {
                $host->id = 'host-test-' . $hostCount;
                $host->name = 'host-test-' . $hostCount;
                $host->saveQuietly();
                $hostCount++;
            });
        });
        $this->hostGroup()->refresh();

        $this->instances = Instance::withoutEvents(function () {
            $instanceCount = 1;
            return Instance::factory(2)->make([
                'vpc_id' => $this->vpc()->id,
                'image_id' => $this->image()->id,
                'vcpu_cores' => 2,
                'ram_capacity' => 2048,
                'availability_zone_id' => $this->availabilityZone()->id,
                'deploy_data' => [
                    'network_id' => $this->network()->id,
                    'volume_capacity' => 20,
                    'volume_iops' => 300,
                    'requires_floating_ip' => false,
                ],
                'host_group_id' => $this->hostGroup()->id,
            ])->each(function ($instance) use (&$instanceCount) {
                $instance->id = 'instance-test-' . $instanceCount;
                $instance->name = 'instance-test-' . $instanceCount;
                $instance->saveQuietly();
                $instanceCount++;
            });
        });
    }

    /**
     * Multiple hosts in hostgroup with instances - not enough resource once target host is removed = reject
     */
    public function testDeleteHostTriggerError()
    {
        Event::fake([Created::class]);
        $hostId = $this->hosts->first()->id;
        $this->delete(
            '/v2/hosts/'.$hostId
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'Host removal will result in insufficient ram capacity for existing instances',
            ]
        )->assertStatus(422);
    }

    /**
     * Multiple hosts in hostgroup with instances - enough resource once target host is removed = accept
     */
    public function testDeleteHost()
    {
        Event::fake([Created::class]);

        // First add another host to make sure we have enough resource
        $host = Model::withoutEvents(function () {
            return Host::factory()->create([
                'id' => 'additional-host',
                'name' => 'additional-host',
                'host_group_id' => $this->hostGroup()->id,
            ]);
        });

        $this->delete(
            '/v2/hosts/'.$host->id
        )->assertStatus(202);
    }

    /**
     * One host in hostgroup with instances = reject
     */
    public function testDeleteHostSingleHostSingleInstance()
    {
        Event::fake([Created::class]);

        // first remove the additional instance and the additional host
        Model::withoutEvents(function () {
            $this->instances[1]->delete();
            $this->hosts[1]->delete();
        });

        // assert that there is one host and one instance
        $this->assertEquals(1, $this->hostGroup()->hosts->count());
        $this->assertEquals(1, $this->hostGroup()->instances()->count());

        $this->delete('/v2/hosts/'.$this->hosts->first()->id)
            ->assertJsonFragment(
                [
                    'title' => 'Validation Error',
                    'detail' => 'Can not delete Host with active instances',
                ]
            )->assertStatus(422);
    }

    /**
     * One host in hostgroup with no instances = accept
     */
    public function testDeleteOneHostNoInstances()
    {
        Event::fake([Created::class]);

        // first remove the additional instance and the additional host
        Model::withoutEvents(function () {
            $this->instances->each(function ($instance) {
                $instance->delete();
            });
            $this->hosts[1]->delete();
        });
        $this->delete('/v2/hosts/'.$this->hosts->first()->id)
            ->assertStatus(202);
    }
}