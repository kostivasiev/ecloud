<?php

namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class IopsModificationTest extends TestCase
{
    use DatabaseMigrations;

    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume',
                [
                    'json' => [
                        'volumeId' => 'vol-test',
                        'sizeGiB' => '100',
                        'shared' => false,
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['uuid' => 'uuid-test-uuid-test-uuid-test']));
            });

        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume/uuid-test-uuid-test-uuid-test/size',
                [
                    'json' => [
                        'sizeGiB' => '100',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
    }

    public function testSetValidIopsValue()
    {
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

        // Initial attachment with 300 limit
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

        $this->instance()->volumes()->attach($this->volume);

        // Patch to the IOPS to 600
        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/uuid-test-uuid-test-uuid-test/iops',
                [
                    'json' => [
                        'limit' => '600',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->patch('/v2/volumes/' . $this->volume->id, [
            'iops' => 600,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase(
            'volumes',
            [
                'id' => $this->volume->id,
                'iops' => 600,
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }

    public function testSetInvalidIopsValue()
    {
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

        $this->instance()->volumes()->attach($this->volume);

        $this->patch('/v2/volumes/' . $this->volume->id, [
            'iops' => 200,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified iops field is not a valid IOPS value (300, 600, 1200, 2500)',
            'source' => 'iops',
        ])->assertResponseStatus(422);
    }

    public function testSetIopsOnUnmountedVolume()
    {
        $this->patch('/v2/volumes/' . $this->volume->id, [
            'iops' => 200,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The Iops value can only be set on mounted volumes',
            'source' => 'iops',
        ])->assertResponseStatus(422);
    }
}
