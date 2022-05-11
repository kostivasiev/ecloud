<?php

namespace Tests\V2\ImageMetadata;

use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testStoreAdminIsSuccess()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $data = [
            'image_id' => $this->image()->id,
            'key' => 'test.key',
            'value' => 'test.value',
        ];

        $this->post('/v2/image-metadata', $data)
            ->assertStatus(201);

        $this->assertDatabaseHas(
            'image_metadata',
            $data,
            'ecloud'
        );
    }

    public function testStoreNotAdminFails()
    {
        $this->post('/v2/image-metadata', [])->assertStatus(401);
    }
}
