<?php

namespace Tests\V1\Datastores;

use App\Models\V1\Datastore;
use App\Models\V1\Pod;
use App\Models\V1\Solution;
use Tests\V1\TestCase;

class GetTest extends TestCase
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
        $count = 2;
        Datastore::factory($count)->create();
        Solution::factory()->create([
            'ucs_reseller_id' => 1,
        ]);

        $this->get(
            '/v1/datastores',
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertStatus(200)
        ->assertJsonFragment([
            'total' => $count,
            'count' => $count,
        ]);
    }

    /**
     * Test for valid item
     * @return void
     */
//    public function testValidItem()
//    {
//        factory(Pod::class, 1)->create();
//        factory(Solution::class, 1)->create();
//        factory(Datastore::class, 1)->create([
//            'reseller_lun_id' => 123,
//        ]);
//
//        $this->get('/v1/datastores/123', [
//            'X-consumer-custom-id' => '1-1',
//            'X-consumer-groups' => 'ecloud.read',
//        ]);
//
//        echo $this->response->getContent();
//        exit(PHP_EOL);
//
//        $this->assertResponseStatus(200);
//    }

    /**
     * Test for invalid item
     * @return void
     */
    public function testInvalidItem()
    {
        $this->get('/v1/datastores/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }

    /**
     * Test loading Default Public Datastore
     * @return void
     * @throws \App\Datastore\Exceptions\DatastoreNotFoundException
     */
    public function testCanLoadPublicDefault()
    {
        $pod = Pod::factory(1)->create([
            'ucs_datacentre_id' => 123,
        ])->first();

        // Backup
        $clusterName = 'MCS_P' . $pod->getKey() . '_VV_VMPUBLICSTORE_SSD_BACKUP';
        Datastore::factory(1)->create([
            'reseller_lun_name' => $clusterName,
        ]);

        $this->assertEquals(
            $clusterName,
            Datastore::getPublicDefault($pod, true)->reseller_lun_name
        );

        // Non-Backup
        $clusterName = 'MCS_P' . $pod->getKey() . '_VV_VMPUBLICSTORE_SSD_NONBACKUP';
        Datastore::factory(1)->create([
            'reseller_lun_name' => $clusterName,
        ]);

        $this->assertEquals(
            $clusterName,
            Datastore::getPublicDefault($pod, false)->reseller_lun_name
        );
    }

    /**
     * Test loading Pod1 Non-Backup Public Datastore
     * @return void
     * @throws \App\Datastore\Exceptions\DatastoreNotFoundException
     */
    public function testCanLoadPublicPod1NonBackup()
    {
        $pod = Pod::factory(1)->create([
            'ucs_datacentre_id' => 14,
        ])->first();

        $clusterName = 'MCS_VV_P1_VMPUBLICSTORE_SSD_NONBACKUP';
        Datastore::factory(1)->create([
            'reseller_lun_name' => $clusterName,
        ]);

        $this->assertEquals(
            $clusterName,
            Datastore::getPublicDefault($pod, false)->reseller_lun_name
        );
    }
}
