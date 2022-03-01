<?php

namespace Tests\V2\ImageMetadata;

use App\Models\V2\ImageParameter;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected ImageParameter $imageParameter;

    public function setUp(): void
    {
        parent::setUp();

        $this->imageMetadata();
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testIndexAdminSucceeds()
    {
        $this->get('/v2/image-metadata')
            ->seeJson([
                'id' => $this->imageMetadata()->id,
                'key' => 'test.key',
                'value' => 'test.value',
            ])
            ->assertResponseStatus(200);
    }

    public function testIndexNotAdminSucceeds()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/image-metadata')
            ->seeJson([
                'id' => $this->imageMetadata()->id,
                'key' => 'test.key',
                'value' => 'test.value',
            ])
            ->assertResponseStatus(200);
    }

    public function testShowAdminSucceeds()
    {
        $this->get('/v2/image-metadata/' . $this->imageMetadata()->id)
            ->seeJson([
                'id' => $this->imageMetadata()->id,
                'key' => 'test.key',
                'value' => 'test.value',
            ])
            ->assertResponseStatus(200);
    }

    public function testShowNotAdminSucceeds()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get('/v2/image-metadata/' . $this->imageMetadata()->id)
            ->seeJson([
                'id' => $this->imageMetadata()->id,
                'key' => 'test.key',
                'value' => 'test.value',
            ])
            ->assertResponseStatus(200);
    }

    public function testImageMetadataSucceeds()
    {
        $this->get('/v2/images/' . $this->image()->id . '/metadata')
            ->seeJson([
                'id' => $this->imageMetadata()->id,
                'key' => 'test.key',
                'value' => 'test.value',
            ])
            ->assertResponseStatus(200);
    }
}