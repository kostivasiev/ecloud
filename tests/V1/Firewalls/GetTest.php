<?php

namespace Tests\V1\Firewalls;

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
//        factory(Firewall::class, 1)->create();
    }

    /**
     * Test for valid collection
     * @return void
     */
    public function testValidCollection()
    {
        factory(Firewall::class, 1)->create();

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
        factory(Firewall::class, 1)->create([
            'servers_id' => 123,
        ]);

        $this->get('/v1/firewalls/123', [
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

    /**
     * Test for inactive item
     * @return void
     */
    public function testInactiveItem()
    {
        factory(Firewall::class, 1)->create([
            'servers_id' => 123,
            'servers_active' => 'n',
        ]);

        $this->get('/v1/firewalls/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }

    /**
     * Test for valid item belonging to a different owner
     * @return void
     */
    public function testInvalidOwnerItem()
    {
        factory(Firewall::class, 1)->create([
            'servers_id' => 123,
            'servers_reseller_id' => 2,
        ]);

        $this->get('/v1/firewalls/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }
}
