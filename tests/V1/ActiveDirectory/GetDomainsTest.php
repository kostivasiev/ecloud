<?php

namespace Tests\V1\ActiveDirectory;

use App\Models\V1\ActiveDirectoryDomain;
use Tests\V1\TestCase;

class GetDomainsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test for valid collection
     * @return void
     */
    public function testValidCollection()
    {
        ActiveDirectoryDomain::factory(2)->create();

        $this->get('/v1/active-directory/domains', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200)
        ->assertJsonFragment([
            'total' => 2,
        ]);
    }


    /**
     * Test for valid item
     * @return void
     */
    public function testValidItem()
    {
        ActiveDirectoryDomain::factory(1)->create([
            'ad_domain_id' => 123,
        ]);

        $this->get('/v1/active-directory/domains/123', [
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
        $this->get('/v1/active-directory/domains/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }

    /**
     * Test for invalid item
     * @return void
     */
    public function testInvalidItemOwner()
    {
        ActiveDirectoryDomain::factory(1)->create([
            'ad_domain_id' => 123,
            'ad_domain_reseller_id' => 2,
        ]);

        $this->get('/v1/active-directory/domains/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);

        $this->assertDatabaseHas('ad_domain', [
            'ad_domain_id' => 123,
            'ad_domain_reseller_id' => 2
        ]);
    }
}
