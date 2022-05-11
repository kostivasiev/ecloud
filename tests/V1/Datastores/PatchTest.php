<?php

namespace Tests\V1\Datastores;

use App\Datastore\Status;
use App\Models\V1\Datastore;
use App\Models\V1\Pod;
use App\Models\V1\San;
use App\Models\V1\Solution;
use App\Models\V1\Storage;
use Illuminate\Validation\Rule;
use Tests\V1\TestCase;

class PatchTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCanPatchDataStore()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 1,
        ]);
        /** @var Pod $pod */
        Pod::factory()->create()->first();
        San::factory()->create(['servers_ecloud_ucs_reseller_id' => 1])->first();
        Storage::factory()->create()->first();
        Datastore::factory()->create(
            [
                "reseller_lun_id" => 1,
                "reseller_lun_reseller_id" => 1,
                "reseller_lun_ucs_reseller_id" => 1,
                "reseller_lun_ucs_site_id" => 0,
                "reseller_lun_friendly_name" => "MY DATASTORE",
                "reseller_lun_status" => "Queued",
                "reseller_lun_type" => "Private",
                "reseller_lun_size_gb" => 1,
                "reseller_lun_name" => "",
                "reseller_lun_wwn" => "",
                "reseller_lun_lun_type" => "DATA",
                "reseller_lun_lun_sub_type" => "",
                "reseller_lun_ucs_storage_id" => 1,
            ]
        )->first();

        $updateName = "Testing Change name";
        $this->patch(
            '/v1/datastores/1',
            [
                'name' => $updateName,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(204);

        $this->assertEquals(Datastore::first()->toArray()['reseller_lun_friendly_name'], $updateName);
    }
}
