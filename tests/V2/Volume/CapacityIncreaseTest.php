<?php

namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use App\Rules\V2\VolumeCapacityIsGreater;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CapacityIncreaseTest extends TestCase
{
    private $volume;

    public function setUp(): void
    {
        parent::setUp();

        // Initial create
        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/volume',
                [
                    'json' => [
                        'volumeId' => 'vol-test',
                        'sizeGiB' => '100',
                        'shared' => false,
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'uuid' => 'uuid-test-uuid-test-uuid-test',
                ]));
            });

        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'iops' => '300',
            'capacity' => '100',
        ]);
    }

    public function testIncreaseSize()
    {
        // Capacity change to 200 fired from the test
        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/uuid-test-uuid-test-uuid-test/size',
                [
                    'json' => [
                        'sizeGiB' => '200',
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        // Attach
        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/vpc-test/instance/i-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'volumes' => []
                ]));
            });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/attach',
                [
                    'json' => [
                        'volumeUUID' => 'uuid-test-uuid-test-uuid-test',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        // Post Attach IOPS update
        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/uuid-test-uuid-test-uuid-test/iops',
                [
                    'json' => [
                        'limit' => '300',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->volume->instances()->attach($this->instance());

        $this->patch('v2/volumes/vol-test', [
            'capacity' => 200,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);
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
