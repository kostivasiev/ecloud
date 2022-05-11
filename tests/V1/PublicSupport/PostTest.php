<?php

namespace Tests\V1\PublicSupport;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\V1\TestCase;

class PostTest extends TestCase
{
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
        ])->assertStatus(201);
    }

    public function testClientCantCreateItem()
    {
        $this->post('/v1/support', [], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
            'X-reseller-id' => 1,
        ])->assertStatus(401);
    }
}
