<?php

namespace Tests\V2\Software;

use Database\Seeders\SoftwareSeeder;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class ImagesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        (new SoftwareSeeder())->run();
    }

    public function testImageSoftware()
    {
        $this->image()->software()->sync(['soft-aaaaaaaa']);

        $this->get('/v2/software/soft-aaaaaaaa/images')
            ->assertJsonFragment([
                'id' => 'img-test',
            ])
            ->assertStatus(200);
    }
}
