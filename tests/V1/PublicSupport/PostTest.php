<?php

namespace Tests\PublicSupport;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\PublicSupport;

class PostTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }


    public function testAdminCanCreateItem()
    {
        $this->post('/v1/support', [], [
            'X-consumer-custom-id' => '0-1',
            'X-consumer-groups' => 'ecloud.write',
            'X-reseller-id' => 1,
        ]);

        $this->assertResponseStatus(201);
    }

    public function testClientCantCreateItem()
    {
        $this->post('/v1/support', [], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
            'X-reseller-id' => 1,
        ]);

        $this->assertResponseStatus(401);
    }
}