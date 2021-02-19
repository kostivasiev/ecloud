<?php

namespace Tests\V2\Volume;

use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Rules\V2\VolumeCapacityIsGreater;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CapacityIncreaseTest extends TestCase
{
    use DatabaseMigrations;

    public function testIncreaseSize()
    {
        // Initial create
        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume',
                [
                    'json' => [
                        'volumeId' => 'v-test',
                        'sizeGiB' => '100',
                        'shared' => false,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['uuid' => 'test-uuid']));
            });

        // Iops fired from creation
        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/test-uuid/iops',
                [
                    'json' => [
                        'limit' => '300',
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        // Capacity fired from creation
        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume/test-uuid/size',
                [
                    'json' => [
                        'sizeGiB' => '100',
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        // Capacity change to 200 fired from the test
        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/test-uuid/size',
                [
                    'json' => [
                        'sizeGiB' => '200',
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        $volume = factory(Volume::class)->create([
            'id' => 'v-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'iops' => '300',
            'capacity' => '100',
        ]);

        $instance = factory(Instance::class)->create([
            'id' => 'i-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'name' => 'GetTest Default',
        ]);

        Log::info('---------------------------------------------');
        $volume->instances()->attach($instance);
        Log::info('---------------------------------------------');

        $this->patch('v2/volumes/v-test', [
            'capacity' => 200,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(200);
    }

    public function testValidationRule()
    {
        $rule = \Mockery::mock(VolumeCapacityIsGreater::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $rule->volume = factory(Volume::class)->create([
            'id' => 'v-test',
            'vmware_uuid' => 'uuid',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

        // Test with a valid value (greater than the original)
        $this->assertTrue($rule->passes('capacity', 200));

        // Test with an invalid value (less than the original)
        $this->assertFalse($rule->passes('capacity', 10));
    }

}
