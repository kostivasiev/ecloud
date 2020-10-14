<?php

namespace Tests\V2\Volume;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Rules\V2\VolumeCapacityIsGreater;
use App\Services\V2\KingpinService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CapacityIncreaseTest extends TestCase
{
    use DatabaseMigrations;

    protected $availabilityZone;
    protected $instance;
    protected $region;
    protected $volume;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->volume = factory(Volume::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'GetTest Default',
        ]);
        $mockKingpinService = \Mockery::mock(new KingpinService(new Client()))->makePartial();
        $mockKingpinService->shouldReceive('put')
            ->withArgs(['/api/v2/vpc/'.$this->vpc->getKey().'/instance/'.$this->instance->getKey().'/volume/'.
                        $this->volume->vmware_uuid.'/size'])
            ->andReturn(
                new Response(200)
            );
        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });
    }

    public function testIncreaseSize()
    {
        $this->patch(
            '/v2/volumes/'.$this->volume->getKey(),
            [
                'capacity' => 200,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )->assertResponseStatus(200);
    }

    public function testValidationRule()
    {
        $rule = \Mockery::mock(VolumeCapacityIsGreater::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $rule->volume = $this->volume;

        // Test with a valid value (greater than the original)
        $this->assertTrue($rule->passes('capacity', 200));

        // Test with an invalid value (less than the original)
        $this->assertFalse($rule->passes('capacity', 10));
    }

}
