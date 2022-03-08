<?php

namespace Tests\V2\Instances;

use App\Models\V2\Image;
use App\Models\V2\Volume;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class CreateImageTest extends TestCase
{
    protected Model $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->volume = Volume::factory()->createOne([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'os_volume' => true,
        ]);
    }

    public function testCreateImageNoVolumes()
    {
        $this->post(
            '/v2/instances/' . $this->instanceModel()->id . '/create-image',
            [
                'name' => 'createImageTest',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'Cannot create an image of an Instance with no attached volumes',
            ]
        )->assertStatus(422);
    }

    public function testCreateImageTest()
    {
        $this->instanceModel()->volumes()->attach($this->volume);
        $this->instanceModel()->saveQuietly();

        // Bind so that we can use the image id
        app()->bind(Image::class, function () {
            return $this->image();
        });

        $this->kingpinServiceMock()
            ->expects('get')->andReturnUsing(function () {
                return new Response(404);
            });

        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/template')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->kingpinServiceMock()->allows('get')
            ->andReturn(
                new Response(200, [], json_encode([
                    'powerState' => KingpinService::INSTANCE_POWERSTATE_POWEREDOFF,
                    'toolsRunningStatus' => KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING,
                ]))
            );

        $this->post(
            '/v2/instances/' . $this->instanceModel()->id . '/create-image',
            [
                'name' => 'createImageTest',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);
    }
}