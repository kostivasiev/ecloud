<?php

namespace Tests\V1\Firewalls;

use App\Models\V1\Firewall;
use Tests\V1\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
//        $this->artisan('db:seed');

        // test firewall
//        Firewall::factory()->create();
    }

    /**
     * Test for valid collection
     * @return void
     */
    public function testValidCollection()
    {
        Firewall::factory()->create();

        $this->get('/v1/firewalls', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200);
    }

    /**
     * Test for valid item
     * @return void
     */
    public function testValidItem()
    {
        Firewall::factory()->create([
            'servers_id' => 123,
        ]);

        $this->get('/v1/firewalls/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200);
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
        ])->assertStatus(404);
    }

    /**
     * Test for inactive item
     * @return void
     */
    public function testInactiveItem()
    {
        Firewall::factory()->create([
            'servers_id' => 123,
            'servers_active' => 'n',
        ]);

        $this->get('/v1/firewalls/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }

    /**
     * Test for valid item belonging to a different owner
     * @return void
     */
    public function testInvalidOwnerItem()
    {
        Firewall::factory()->create([
            'servers_id' => 123,
            'servers_reseller_id' => 2,
        ]);

        $this->get('/v1/firewalls/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }
}
