<?php

namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    private $volume;

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
                return new Response(200, [], json_encode([
                    'uuid' => 'uuid-test-uuid-test-uuid-test',
                ]));
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

    public function testFailedDeleteDueToAssignedInstance()
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
        $this->delete('/v2/volumes/' . $this->volume->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);
        $this->assertNull($this->volume->deleted_at);
    }

    public function testSuccessfulDelete()
    {
        $this->kingpinServiceMock()->expects('delete')
            ->withArgs(['/api/v1/vpc/vpc-test/volume/uuid-test-uuid-test-uuid-test'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->delete('/v2/volumes/' . $this->volume->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->volume->refresh();
        $this->assertNotNull($this->volume->deleted_at);
    }
}
