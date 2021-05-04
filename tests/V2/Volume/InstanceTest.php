<?php

namespace Tests\V2\Volume;

use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class InstanceTest extends TestCase
{
    public function testGetInstances()
    {
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
                return new Response(200, [], json_encode(['uuid' => 'uuid-test-uuid-test-uuid-test']));
            });

        $volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'name' => 'Volume',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);

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

        $volume->instances()->attach($this->instance());

        $this->get('/v2/volumes/' . $volume->id . '/instances', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->instance()->id,
        ])->assertResponseStatus(200);
    }
}
