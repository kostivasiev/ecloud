<?php

namespace Tests\V1\Pods;

use App\Models\V1\Pod;
use Tests\Traits\ResellerDatabaseMigrations;
use Tests\V1\TestCase;

class GetTest extends TestCase
{
    use ResellerDatabaseMigrations;

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
        Pod::factory($total)->create();

        $this->get('/v1/pods', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'total' => $total,
        ])->assertStatus(200);
    }

    /**
     * Test for valid item
     * @return void
     */
    public function testValidItem()
    {
        Pod::factory()->create([
            'ucs_datacentre_id' => 123,
        ]);

        $this->get('/v1/pods/123', [
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
        $this->get('/v1/pods/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }

    /**
     * Test pod with api disabled is hidden
     * @return void
     */
    public function testHiddenApiDisabled()
    {
        Pod::factory()->create([
            'ucs_datacentre_id' => 123,
            'ucs_datacentre_api_enabled' => 'No',
        ]);

        $this->get('/v1/pods/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }

    /**
     * Test customer pod hidden from other users
     * @return void
     */
    public function testHiddenClientPod()
    {
        Pod::factory()->create([
            'ucs_datacentre_id' => 123,
            'ucs_datacentre_reseller_id' => 999,
        ]);

        $this->get('/v1/pods/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }

    /**
     * Test GET pod VCL / VCE server id's (admin)
     */
    public function testVclVceServerIdAdmin()
    {
        $pod = Pod::factory()->create([
            'ucs_datacentre_id' => 123,
            'ucs_datacentre_vcl_server_id' => 12345,
            'ucs_datacentre_vce_server_id' => 54321,
        ])->first();

        $this->getJson('/v1/pods/123', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])->assertJsonFragment([
            'vce_server_id' => (int) $pod->ucs_datacentre_vce_server_id,
            'vcl_server_id' => (int) $pod->ucs_datacentre_vcl_server_id
        ])->assertStatus(200);
    }

    /**
     * Test GET pod VCL / VCE server id's (not admin)
     */
    public function testVclVceServerIdNotAdmin()
    {
        $pod = Pod::factory()->create([
            'ucs_datacentre_id' => 123,
            'ucs_datacentre_vcl_server_id' => 12345,
            'ucs_datacentre_vce_server_id' => 54321,
        ])->first();

        $this->get('/v1/pods/123', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])
            ->assertStatus(200)
            ->assertJsonMissing([
                'vce_server_id' => (int) $pod->ucs_datacentre_vce_server_id,
                'vcl_server_id' => (int) $pod->ucs_datacentre_vcl_server_id
            ]);
    }

    public function testFilteringCollectionByService()
    {
        Pod::factory()->create([
            'ucs_datacentre_id' => 123,
            'ucs_datacentre_public_enabled' => true,
        ]);

        Pod::factory()->create([
            'ucs_datacentre_id' => 321,
            'ucs_datacentre_public_enabled' => false,
        ]);

        $this->get('/v1/pods?services.public:eq=true', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'total' => 1,
        ])->assertStatus(200);
    }

    /**
     * Test GET pod VMWare API URL (Admin)
     */
    public function testVmwareApiUrlAdmin()
    {
        $pod = Pod::factory()->create([
            'ucs_datacentre_id' => 123,
            'ucs_datacentre_vmware_api_url' => 'http://example.com'
        ])->first();

        $this->get('/v1/pods/123', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])->assertStatus(200)
            ->assertJsonFragment([
                'mgmt_api_url' => $pod->ucs_datacentre_vmware_api_url
            ]);
    }

    /**
     * Test GET pod VMWare API URL (Not admin)
     */
    public function testVmwareApiUrlNotAdmin()
    {
        $pod = Pod::factory()->create([
            'ucs_datacentre_id' => 123,
            'ucs_datacentre_vmware_api_url' => 'http://example.com'
        ])->first();

        $this->get('/v1/pods/123', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])
            ->assertStatus(200)
            ->assertJsonMissing([
                'mgmt_api_url' => $pod->ucs_datacentre_vmware_api_url
            ]);
    }
}
