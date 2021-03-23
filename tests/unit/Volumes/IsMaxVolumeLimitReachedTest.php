<?php

namespace Tests\unit\Volumes;

use App\Models\V2\Volume;
use App\Rules\V2\IsMaxVolumeLimitReached;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxVolumeLimitReachedTest extends TestCase
{
    use DatabaseMigrations;

    public function testLimits()
    {
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

        $volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('volume.instance.limit', 1);
        $rule = new IsMaxVolumeLimitReached();

        // Test with one volume limit, and then attach that volume
        $this->assertTrue($rule->passes('', $this->instance()->id));

        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/vpc-test/instance/i-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'volumes' => [
                        ['uuid' => 'uuid-test-uuid-test-uuid-test']
                    ]
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

        $this->instance()->volumes()->attach($volume);

        // Now assert that we're at the limit
        $this->assertFalse($rule->passes('', $this->instance()->id));
    }
}