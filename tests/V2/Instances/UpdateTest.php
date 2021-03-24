<?php

namespace Tests\V2\Instances;

use App\Models\V2\ApplianceVersionData;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidDataIsSuccessful()
    {
        $this->patch(
            '/v2/instances/' . $this->instance()->id,
            [
                'name' => 'Changed',
                'backup_enabled' => true,
                'host_group_id' => $this->hostGroup()->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'instances',
            [
                'id' => $this->instance()->id,
                'name' => 'Changed'
            ],
            'ecloud'
        )
            ->assertResponseStatus(200);

        $this->instance()->refresh();
        $this->assertEquals('Changed', $this->instance()->name);
        $this->assertTrue($this->instance()->backup_enabled);
    }

    public function testAdminCanModifyLockedInstance()
    {
        // Lock the instance
        $this->instance()->locked = true;
        $this->instance()->save();
        $data = [
            'name' => 'Changed',
        ];
        $this->patch(
            '/v2/instances/' . $this->instance()->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'instances',
            [
                'id' => $this->instance()->id,
                'name' => 'Changed'
            ],
            'ecloud'
        )
            ->assertResponseStatus(200);
    }

    public function testScopedAdminCanNotModifyLockedInstance()
    {
        $this->instance()->locked = true;
        $this->instance()->save();
        $this->patch(
            '/v2/instances/' . $this->instance()->id,
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
        $this->instance()->locked = true;
        $this->instance()->save();
        $this->patch(
            '/v2/instances/' . $this->instance()->id,
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
        $this->instance()->locked = false;
        $this->instance()->save();

        $data = [
            'name' => 'Changed',
        ];
        $this->patch(
            '/v2/instances/' . $this->instance()->id,
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'instances',
            [
                'id' => $this->instance()->id,
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
            'appliance_version_uuid' => $this->applianceVersion()->appliance_version_uuid,
        ]);

        $data = [
            'ram_capacity' => 3072,
        ];

        $this->patch(
            '/v2/instances/' . $this->instance()->id,
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
            'appliance_version_uuid' => $this->applianceVersion()->appliance_version_uuid,
        ]);

        $data = [
            'vcpu_cores' => 6,
        ];

        $this->patch(
            '/v2/instances/' . $this->instance()->id,
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
