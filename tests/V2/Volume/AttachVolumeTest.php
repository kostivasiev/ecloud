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
    protected AvailabilityZone $availability_zone;
    protected Credential $credential;
    protected Instance $instance;
    protected Region $region;
    protected Volume $volume;
    protected Vpc $vpc;

    protected $kingpinService;

    public function setUp(): void
    {
        parent::setUp();

        app()->bind('encrypter', function () {
            $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
                ->shouldAllowMockingProtectedMethods();
            $mockEncryptionServiceProvider->shouldReceive('encrypt')
                ->andReturn('EnCrYpTeD-pAsSwOrD');
            $mockEncryptionServiceProvider->shouldReceive('decrypt')
                ->andReturn('somepassword');
            return $mockEncryptionServiceProvider;
        });

        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->applianceVersion = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->id,
            'appliance_version_id' => $this->applianceVersion->uuid,
            'availability_zone_id' => $this->availability_zone->getKey(),
        ]);
        $this->credential = factory(Credential::class)->create([
            'resource_id' => $this->availability_zone->getKey()
        ]);
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availability_zone->getKey(),
        ]);

        app()->bind(KingpinService::class, function () {
            $mockKingpinService = \Mockery::mock(new KingpinService(new Client()));
            $mockKingpinService->shouldReceive('post')
                ->withArgs([
                    '/api/v2/vpc/'.$this->instance->vpc_id.
                    '/instance/'.$this->instance->id.
                    '/volume/attach',
                    [
                        'json' => [
                            'volumeUUID' => $this->volume->vmware_uuid,
                            'shared' => true,
                            'unitNumber' => 0
                        ]
                    ]
                ])
                ->andReturn(
                    new Response(200)
                );
            $mockKingpinService->shouldReceive('put')
                ->withArgs([
                    '/api/v2/vpc/'.$this->instance->vpc_id.
                    '/instance/'.$this->instance->id.
                    '/volume/'.$this->volume->vmware_uuid.
                    '/iops',
                    [
                        'json' => [
                            'limit' => $this->volume->iops
                        ]
                    ]
                ])
                ->andReturn(
                    new Response(200)
                );
            return $mockKingpinService;
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
            'title' => 'Duplicated Request',
            'detail' => 'The volume is already attached to the specified instance',
        ])->assertResponseStatus(400);
    }
}
