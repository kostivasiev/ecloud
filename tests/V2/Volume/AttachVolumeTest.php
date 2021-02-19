<?php
namespace Tests\V2\Volume;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\KingpinService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AttachVolumeTest extends TestCase
{
    use DatabaseMigrations;

    protected Appliance $appliance;
    protected ApplianceVersion $applianceVersion;
    protected Instance $instance;
    protected Region $region;
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->applianceVersion = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc()->id,
            'appliance_version_id' => $this->applianceVersion->uuid,
            'availability_zone_id' => $this->availabilityZone()->getKey(),
        ]);
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc()->getKey(),
            'availability_zone_id' => $this->availabilityZone()->getKey(),
        ]);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instance->vpc_id .
                '/instance/' . $this->instance->id .
                '/volume/attach',
                [
                    'json' => [
                        'volumeUUID' => $this->volume->vmware_uuid
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                new Response(200);
            });
        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/' . $this->instance->vpc_id .
                '/instance/' . $this->instance->id .
                '/volume/' . $this->volume->vmware_uuid .
                '/iops',
                [
                    'json' => [
                        'limit' => $this->volume->iops
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                new Response(200);
            });
    }

    public function testAttachingVolumeAndSettingIops()
    {
        $this->assertEquals(
            0,
            $this->instance->volumes()->get()->count(),
            'Volumes are attached to the instance'
        );

        $this->post(
            '/v2/volumes/'.$this->volume->id.'/attach',
            [
                'instance_id' => $this->instance->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);

        $this->assertNotEquals(
            0,
            $this->instance->volumes()->get()->count(),
            'No volumes are attached to the instance'
        );

        $this->post(
            '/v2/volumes/'.$this->volume->id.'/attach',
            [
                'instance_id' => $this->instance->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified volume is already mounted on this instance',
            'status' => 422,
            'source' => 'instance_id',
        ])->assertResponseStatus(422);
    }
}
