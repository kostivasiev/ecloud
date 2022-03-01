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
        $total = 2;
        factory(ActiveDirectoryDomain::class, $total)->create();

        $this->get('/v1/active-directory/domains', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => $total,
        ]);
    }


    /**
     * Test for valid item
     * @return void
     */
    public function testValidItem()
    {
        factory(ActiveDirectoryDomain::class, 1)->create([
            'ad_domain_id' => 123,
        ]);

        $this->get('/v1/active-directory/domains/123', [
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
        $this->get('/v1/active-directory/domains/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }

    /**
     * Test for invalid item
     * @return void
     */
    public function testInvalidItemOwner()
    {
        factory(ActiveDirectoryDomain::class, 1)->create([
            'ad_domain_id' => 123,
            'ad_domain_reseller_id' => 2,
        ]);

        $this->get('/v1/active-directory/domains/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404) && $this->seeInDatabase('ad_domain', [
            'ad_domain_id' => 123,
            'ad_domain_reseller_id' => 2
        ]);
    }
}
