<?php

namespace Tests\V1\VolumeSets;

use App\Models\V1\Datastore;
use App\Models\V1\Pod;
use App\Models\V1\San;
use App\Models\V1\Storage;
use App\Models\V1\VolumeSet;
use App\Services\Artisan\V1\ArtisanService;
use Tests\V1\TestCase;

class DeleteTest extends TestCase
{
    public Datastore $dataStore;
    public Pod $pod;
    public San $san;
    public Storage $storage;
    public VolumeSet $volumeSet;

    public function setUp(): void
    {
        parent::setUp();
        $this->addReseller();
        $this->volumeSet = VolumeSet::factory()->create([
            'ucs_reseller_id' => 1,
        ]);
        $this->pod = Pod::factory()->create();
        $this->san = San::factory()->create();
        $this->storage = Storage::factory()->create([
            'ucs_datacentre_id' => $this->san->getKey(),
            'server_id' => $this->pod->getKey(),
            'qos_enabled' => 'Yes',
        ]);
        $this->dataStore =
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
                    "reseller_lun_ucs_storage_id" => $this->storage->getKey(),
                ]
            )->first();
    }

    public function testDeleteDatastoreSuccessful()
    {
        app()->bind(ArtisanService::class, function () {
            $mock = \Mockery::mock(ArtisanService::class)
                ->makePartial();
            $mock->allows('removeVolumeFromVolumeSet')
                ->andReturnTrue();
            return $mock;
        });

        $this->delete(
            sprintf(
                '/v1/volumesets/%s/datastores/%d',
                $this->volumeSet->getKey(),
                $this->dataStore->getKey()
            ),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-reseller-id' => 1,
            ]
        )->assertStatus(204);
    }

    public function testDeleteDatastoreUnsuccessful()
    {
        app()->bind(ArtisanService::class, function () {
            $mock = \Mockery::mock(ArtisanService::class)
                ->makePartial();
            $mock->allows('removeVolumeFromVolumeSet')
                ->andReturnFalse();
            return $mock;
        });

        $this->delete(
            sprintf(
                '/v1/volumesets/%s/datastores/%d',
                $this->volumeSet->getKey(),
                $this->dataStore->getKey()
            ),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-reseller-id' => 1,
            ]
        )->assertJsonFragment([
            'title' => 'Storage Network Exception',
            'detail' => 'Failed to remove datastore to volume set: '
        ])->assertStatus(503);
    }

    public function testDeleteVolumesetSuccessful()
    {
        $this->delete(
            sprintf('/v1/volumesets/%s', $this->volumeSet->getKey()),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-reseller-id' => 1,
            ]
        )->assertStatus(204);
    }
}