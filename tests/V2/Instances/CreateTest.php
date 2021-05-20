<?php

namespace Tests\V2\Instances;

use App\Models\V2\ApplianceVersion;
use App\Models\V2\ApplianceVersionData;
use App\Models\V2\Image;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Devices\AdminClient;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    protected ApplianceVersion $applianceVersion;
    protected Image $image;

    public function setUp(): void
    {
        parent::setUp();

        $mockAdminDevices = \Mockery::mock(AdminClient::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind(AdminClient::class, function () use ($mockAdminDevices) {
            $mockedResponse = new \stdClass();
            $mockedResponse->category = "Linux";
            $mockAdminDevices->shouldReceive('licenses->getById')->andReturn($mockedResponse);
            return $mockAdminDevices;
        });
    }

    public function testApplianceSpecDefaultConfigFallbacks()
    {
        Model::withoutEvents(function () {
            $this->applianceVersion = factory(ApplianceVersion::class)->create([
                'appliance_version_appliance_id' => 123,
                'appliance_version_uuid' => 'e8321e4a-2306-4b9d-bd2d-9cd42f054197'
            ]);
            $this->image = factory(Image::class)->create([
                'id' => 'img-abcdef12',
                'appliance_version_id' => $this->applianceVersion->id,
                'platform' => 'Linux'
            ]);
        });

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image->id,
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
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Specified vcpu cores is above the maximum of ' . config('instance.cpu_cores.max'),
                'status' => 422,
                'source' => 'ram_capacity'
            ])
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Specified ram capacity is below the minimum of ' . config('instance.ram_capacity.min'),
                'status' => 422,
                'source' => 'ram_capacity'
            ])
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Specified volume capacity is below the minimum of ' . config('volume.capacity.linux.min'),
                'status' => 422,
                'source' => 'volume_capacity'
            ])
            ->assertResponseStatus(422);

        //dd($this->response->getContent());
    }

    public function testApplianceSpecRamMin()
    {
        Model::withoutEvents(function () {
            $this->applianceVersion = factory(ApplianceVersion::class)->create([
                'appliance_version_appliance_id' => 123,
                'appliance_version_uuid' => 'e8321e4a-2306-4b9d-bd2d-9cd42f054197'
            ]);
            factory(ApplianceVersionData::class)->create([
                'key' => 'ukfast.spec.ram.min',
                'value' => 2048,
                'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
            ]);
            $this->image = factory(Image::class)->create([
                'id' => 'img-abcdef12',
                'appliance_version_id' => $this->applianceVersion->id,
            ]);
        });

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image->id,
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
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Specified ram capacity is below the minimum of 2048',
                'status' => 422,
                'source' => 'ram_capacity'
            ])->assertResponseStatus(422);
    }

    public function testApplianceSpecVolumeMin()
    {
        Model::withoutEvents(function () {
            $this->applianceVersion = factory(ApplianceVersion::class)->create([
                'appliance_version_appliance_id' => 123,
                'appliance_version_uuid' => 'e8321e4a-2306-4b9d-bd2d-9cd42f054197'
            ]);
            factory(ApplianceVersionData::class)->create([
                'key' => 'ukfast.spec.volume.min',
                'value' => 50,
                'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
            ]);
            $this->image = factory(Image::class)->create([
                'id' => 'img-abcdef12',
                'appliance_version_id' => $this->applianceVersion->id,
            ]);
        });

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image->id,
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
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Specified volume capacity is below the minimum of 50',
                'status' => 422,
                'source' => 'volume_capacity'
            ])->assertResponseStatus(422);
    }

    public function testApplianceSpecVcpuMin()
    {
        Model::withoutEvents(function () {
            $this->applianceVersion = factory(ApplianceVersion::class)->create([
                'appliance_version_appliance_id' => 123,
                'appliance_version_uuid' => 'e8321e4a-2306-4b9d-bd2d-9cd42f054197'
            ]);
            factory(ApplianceVersionData::class)->create([
                'key' => 'ukfast.spec.cpu_cores.min',
                'value' => 2,
                'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
            ]);
            $this->image = factory(Image::class)->create([
                'id' => 'img-abcdef12',
                'appliance_version_id' => $this->applianceVersion->id,
            ]);
        });

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image->id,
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
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Specified vcpu cores is below the minimum of 2',
                'status' => 422,
                'source' => 'vcpu_cores'
            ])->assertResponseStatus(422);
    }

    public function testMaxInstancePerVpcLimitReached()
    {
        Config::set('instance.max_limit.per_vpc', 0);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Model::withoutEvents(function () {
            $this->applianceVersion = factory(ApplianceVersion::class)->create([
                'appliance_version_appliance_id' => 123,
                'appliance_version_uuid' => 'e8321e4a-2306-4b9d-bd2d-9cd42f054197'
            ]);
            factory(ApplianceVersionData::class)->create([
                'key' => 'ukfast.spec.cpu_cores.min',
                'value' => 2,
                'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
            ]);
            $this->image = factory(Image::class)->create([
                'id' => 'img-abcdef12aa',
                'appliance_version_id' => $this->applianceVersion->id,
            ]);
        });

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image->id,
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
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The maximum number of 0 Instances per Vpc has been reached',
            ]
        )->assertResponseStatus(422);
    }

    public function testMaxInstancePerCustomerLimitReached()
    {
        Config::set('instance.max_limit.total', 0);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Model::withoutEvents(function () {
            $this->applianceVersion = factory(ApplianceVersion::class)->create([
                'appliance_version_appliance_id' => 123,
                'appliance_version_uuid' => 'e8321e4a-2306-4b9d-bd2d-9cd42f054197'
            ]);
            factory(ApplianceVersionData::class)->create([
                'key' => 'ukfast.spec.cpu_cores.min',
                'value' => 2,
                'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
            ]);
            $this->image = factory(Image::class)->create([
                'id' => 'img-abcdef12aa',
                'appliance_version_id' => $this->applianceVersion->id,
            ]);
        });

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image->id,
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
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The maximum number of 0 Instances per Customer have been reached',
            ]
        )->assertResponseStatus(422);
    }
}
