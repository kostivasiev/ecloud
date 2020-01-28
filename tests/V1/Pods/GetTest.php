<?php

namespace Tests\Pods;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\Pod;

class GetTest extends TestCase
{
    use DatabaseMigrations;

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
        $total = rand(1, 2);
        factory(Pod::class, $total)->create();

        $this->get('/v1/pods', [
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
        factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 123,
        ]);

        $this->get('/v1/pods/123', [
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
        $this->get('/v1/pods/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }

    /**
     * Test pod with api disabled is hidden
     * @return void
     */
    public function testHiddenApiDisabled()
    {
        factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 123,
            'ucs_datacentre_api_enabled' => 'No',
        ]);

        $this->get('/v1/pods/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }

    /**
     * Test customer pod hidden from other users
     * @return void
     */
    public function testHiddenClientPod()
    {
        factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 123,
            'ucs_datacentre_reseller_id' => 999,
        ]);

        $this->get('/v1/pods/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }

    public function testFilteringCollectionByService()
    {
        factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 123,
            'ucs_datacentre_public_enabled' => true,
        ]);

        factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 321,
            'ucs_datacentre_public_enabled' => false,
        ]);

        $this->get('/v1/pods?services.public:eq=true', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => 1,
        ]);
    }
}
