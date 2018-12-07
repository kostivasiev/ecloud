<?php

namespace Tests\Firewalls;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\Firewall;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
//        $this->artisan('db:seed');

        // test firewall
        factory(Firewall::class, 1)->create();
    }

    /**
     * Test for valid collection
     * @return void
     */
    public function testValidCollection()
    {
        $this->get('/v1/firewalls', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200);
    }

    /**
     * Test for valid item
     * @return void
     */
    public function testValidItem()
    {
        $this->get('/v1/firewalls/1', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200);
    }

    /**
     * Test for invalid item
     * @return void
     */
    public function testInvalidItem()
    {
        $this->get('/v1/firewalls/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }
}
