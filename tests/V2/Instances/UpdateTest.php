<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\ApplianceVersionData;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $vpc;
    protected $appliance;
    protected $applianceVersion;
    protected $instance;
    protected $region;
    protected $availability_zone;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();
        $this->applianceVersion = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'UpdateTest Default',
            'appliance_version_id' => $this->applianceVersion->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'backup_enabled' => false,
        ]);
    }

    public function testValidDataIsSuccessful()
    {
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            [
                'name' => 'Changed',
                'backup_enabled' => true,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'instances',
            [
                'id' => $this->instance->getKey(),
                'name' => 'Changed'
            ],
            'ecloud'
        )
            ->assertResponseStatus(200);

        $instance = Instance::findOrFail($this->instance->getKey());
        $this->assertEquals($this->vpc->getKey(), $instance->vpc_id);
        $this->assertTrue($instance->backup_enabled);
    }

    public function testAdminCanModifyLockedInstance()
    {
        // Lock the instance
        $this->instance->locked = true;
        $this->instance->save();
        $data = [
            'name' => 'Changed',
        ];
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'instances',
            [
                'id' => $this->instance->getKey(),
                'name' => 'Changed'
            ],
            'ecloud'
        )
            ->assertResponseStatus(200);
    }

    public function testScopedAdminCanNotModifyLockedInstance()
    {
        $this->instance->locked = true;
        $this->instance->save();
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            [
                'name' => 'Testing Locked Instance',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => '1',
            ]
        )
            ->seeJson([
                'title' => 'Forbidden',
                'detail' => 'The specified instance is locked',
                'status' => 403,
            ])
            ->assertResponseStatus(403);
    }

    public function testLockedInstanceIsNotEditable()
    {
        // Lock the instance
        $this->instance->locked = true;
        $this->instance->save();
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            [
                'name' => 'Testing Locked Instance',
            ],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Forbidden',
                'detail' => 'The specified instance is locked',
                'status' => 403,
            ])
            ->assertResponseStatus(403);

        // Unlock the instance
        $this->instance->locked = false;
        $this->instance->save();

        $data = [
            'name' => 'Changed',
        ];
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'instances',
            [
                'id' => $this->instance->getKey(),
                'name' => 'Changed'
            ],
            'ecloud'
        )
            ->assertResponseStatus(200);
    }

    public function testApplianceSpecRamMax()
    {
        factory(ApplianceVersionData::class)->create([
            'key' => 'ukfast.spec.ram.max',
            'value' => 2048,
            'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
        ]);

        $data = [
            'ram_capacity' => 3072,
        ];

        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Specified ram capacity is above the maximum of 2048',
                'status' => 422,
                'source' => 'ram_capacity'
            ])->assertResponseStatus(422);
    }

    public function testApplianceSpecVcpuMax()
    {
        factory(ApplianceVersionData::class)->create([
            'key' => 'ukfast.spec.cpu_cores.max',
            'value' => 5,
            'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
        ]);

        $data = [
            'vcpu_cores' => 6,
        ];

        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'Specified vcpu cores is above the maximum of 5',
                'status' => 422,
                'source' => 'vcpu_cores'
            ])->assertResponseStatus(422);
    }
}
