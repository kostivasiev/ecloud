<?php

namespace Tests\V2\Instances;

use App\Models\V2\Image;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class CreateImageTest extends TestCase
{
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'os_volume' => true,
        ]);
    }

    public function testCreateImageNoVolumes()
    {
        $this->post(
            '/v2/instances/' . $this->instance()->id . '/create-image',
            [
                'name' => 'createImageTest',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'Cannot create an image of an Instance with no attached volumes',
            ]
        )->assertResponseStatus(422);
    }

    public function testCreateImageTest()
    {
        $this->instance()->volumes()->attach($this->volume);
        $this->instance()->saveQuietly();

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

        $this->post(
            '/v2/instances/' . $this->instance()->id . '/create-image',
            [
                'name' => 'createImageTest',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);
    }
}