<?php

namespace Tests\V2\Instances;

use App\Events\V2\Task\Created;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\HostGroup;
use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Support\Sync;
use Database\Seeders\SoftwareSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    protected ApplianceVersion $applianceVersion;
    protected Image $image;

    public function testSpecDefaultConfigFallbacks()
    {
        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 11,
            'ram_capacity' => 512,
            'volume_capacity' => 10,
            'volume_iops' => 600,
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'Specified vcpu cores is above the maximum of ' . config('instance.cpu_cores.max'),
                'status' => 422,
                'source' => 'ram_capacity'
            ])
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'Specified ram capacity is below the minimum of ' . config('instance.ram_capacity.min'),
                'status' => 422,
                'source' => 'ram_capacity'
            ])
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'Specified volume capacity is below the minimum of ' . config('volume.capacity.linux.min'),
                'status' => 422,
                'source' => 'volume_capacity'
            ])
            ->assertStatus(422);

        //dd($this->response->getContent());
    }

    public function testImageMetadataSpecRamMin()
    {
        ImageMetadata::factory()->create([
            'key' => 'ukfast.spec.ram.min',
            'value' => 2048,
            'image_id' => $this->image()->id
        ]);

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'Specified ram capacity is below the minimum of 2048',
                'status' => 422,
                'source' => 'ram_capacity'
            ])->assertStatus(422);
    }

    public function testImageMetadataSpecVolumeMin()
    {
        ImageMetadata::factory()->create([
            'key' => 'ukfast.spec.volume.min',
            'value' => 50,
            'image_id' => $this->image()->id
        ]);

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'Specified volume capacity is below the minimum of 50',
                'status' => 422,
                'source' => 'volume_capacity'
            ])->assertStatus(422);
    }

    public function testImageMetadataSpecVcpuMin()
    {
        ImageMetadata::factory()->create([
            'key' => 'ukfast.spec.cpu_cores.min',
            'value' => 2,
            'image_id' => $this->image()->id
        ]);

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'Specified vcpu cores is below the minimum of 2',
                'status' => 422,
                'source' => 'vcpu_cores'
            ])->assertStatus(422);
    }

    public function testMaxInstancePerVpcLimitReached()
    {
        Config::set('instance.max_limit.per_vpc', 0);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 2,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The maximum number of 0 Instances per Vpc has been reached',
            ]
        )->assertStatus(422);
    }

    public function testMaxInstancePerCustomerLimitReached()
    {
        Config::set('instance.max_limit.total', 0);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 2,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The maximum number of 0 Instances per Customer have been reached',
            ]
        )->assertStatus(422);
    }

    public function testVpcOrNetworkFailCausesFail()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Vpc Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->vpc());
            $model->save();

            $model = new Task([
                'id' => 'sync-test-2',
                'failure_reason' => 'Network Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->network());
            $model->save();
        });

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 2,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id resource currently has the status of \'failed\' and cannot be used',
            ]
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified network id resource currently has the status of \'failed\' and cannot be used',
            ]
        )->assertStatus(422);
    }

    public function testHostgroupWithNoHostsCausesFail()
    {
        $hostGroup = HostGroup::withoutEvents(function () {
            return HostGroup::factory()->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);
        });

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'host_group_id' => $hostGroup->id,
            'vcpu_cores' => 2,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'There are no hosts assigned to the specified host group id',
                'source' => 'host_group_id',
            ]
        )->assertStatus(422);
    }

    public function testAlreadyAssignedFloatingIpCausesFail()
    {
        $this->floatingIpResource = FloatingIpResource::factory()->make();
        $this->floatingIpResource->floatingIp()->associate($this->floatingIp());
        $this->floatingIpResource->resource()->associate($this->ipAddress());
        $this->floatingIpResource->save();

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 2,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
            'floating_ip_id' => $this->floatingIp()->id
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The Floating IP is already assigned to a resource',
                'source' => 'floating_ip_id',
            ]
        )->assertStatus(422);
    }

    public function testNetworkFromAnotherVpcCausesFail()
    {
        $secondVpc = Model::withoutEvents(function () {
            return Vpc::factory()->create([
                'id' => 'vpc-second',
                'region_id' => $this->region()->id
            ]);
        });
        $secondRouter = Model::withoutEvents(function () use ($secondVpc) {
            return Router::factory()->create([
                'id' => 'rtr-second',
                'vpc_id' => $secondVpc->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'router_throughput_id' => $this->routerThroughput()->id,
            ]);
        });
        $secondNetwork = Model::withoutEvents(function () use ($secondRouter) {
            return Network::factory()->create([
                'id' => 'net-second',
                'name' => 'Manchester Network',
                'subnet' => '10.0.0.0/24',
                'router_id' => $secondRouter->id
            ]);
        });

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $secondNetwork->id,
            'vcpu_cores' => 4,
            'ram_capacity' => 1024,
            'volume_capacity' => 30,
            'volume_iops' => 600,
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'Resources must be in the same Vpc'
            ]
        )->assertStatus(422);
    }

    public function testOptionalSoftwareWrongPlatformFails()
    {
        (new SoftwareSeeder())->run();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->image()->setAttribute('platform', Image::PLATFORM_WINDOWS)->save();

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'volume_capacity' => 40,
            'volume_iops' => 600,
            'software_ids' => [
                'soft-aaaaaaaa'
            ]
        ];

        $this->post('/v2/instances', $data)
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'Software platform does not match image platform',
                'status' => 422,
                'source' => 'software_ids.0'
            ])->assertStatus(422);
    }

    public function testOptionalSoftwareCorrectPlatformPasses()
    {
        Event::fake(Created::class);

        (new SoftwareSeeder())->run();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'volume_capacity' => 40,
            'volume_iops' => 600,
            'software_ids' => [
                'soft-aaaaaaaa'
            ]
        ];

        $this->post('/v2/instances', $data)->assertStatus(202);
    }
}
