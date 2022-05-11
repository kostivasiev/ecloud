<?php

namespace Tests\V1\VolumeSets;

use App\Models\V1\Datastore;
use App\Models\V1\HostSet;
use App\Models\V1\Pod;
use App\Models\V1\San;
use App\Models\V1\Solution;
use App\Models\V1\Storage;
use App\Models\V1\VolumeSet;
use App\Services\Artisan\V1\ArtisanService;
use Tests\V1\TestCase;

class CreateTest extends TestCase
{
    public Solution $solution;
    public Pod $pod;
    public San $san;
    public Storage $storage;
    public HostSet $hostSet;

    public function setUp(): void
    {
        parent::setUp();

        $this->solution = Solution::factory()->create();
        $this->pod = Pod::factory()->create();
        $this->solution->setAttribute('ucs_reseller_datacentre_id', $this->pod->getKey())->saveQuietly();
        $this->san = San::factory()->create();
        $this->storage = Storage::factory()->create([
            'ucs_datacentre_id' => $this->san->getKey(),
            'server_id' => $this->pod->getKey(),
            'qos_enabled' => 'Yes',
        ]);
        $this->hostSet = HostSet::factory()->create();
        $this->solution->setAttribute('ucs_reseller_id', $this->hostSet->getAttribute('ucs_reseller_id'))
            ->saveQuietly();
    }

    public function testCreateResourceSuccessfully()
    {
        app()->bind(ArtisanService::class, function () {
            $mock = \Mockery::mock(ArtisanService::class)
                ->makePartial();
            $mock->allows('createVolumeSet')
                ->andReturnUsing(function () {
                    $response = new \StdClass();
                    $response->name = 'RaNdOmVoLuMeSeTnAmE';
                    return $response;
                });
            return $mock;
        });
        $this->post('/v1/volumesets', [
            'solution_id' => $this->solution->getKey(),
            'san_id' => $this->san->getKey(),
        ], $this->validWriteHeaders)
            ->assertStatus(201);
    }

    public function testCreateResourceUnsuccessfully()
    {
        $this->post('/v1/volumesets', [
            'solution_id' => '',
            'san_id' => '',
        ], $this->validWriteHeaders)
            ->assertStatus(422);
    }

    public function testSetIopsSuccessfully()
    {
        $volumeSet = VolumeSet::factory()->create();
        $volumeSet->setAttribute('ucs_reseller_id', $this->solution->getKey())
            ->saveQuietly();
        $this->storage
            ->setAttribute('server_id', $this->san->getKey())
            ->saveQuietly();

        app()->bind(ArtisanService::class, function () {
            $mock = \Mockery::mock(ArtisanService::class)
                ->makePartial();
            $mock->allows('setIOPS')
                ->andReturnTrue();
            return $mock;
        });

        $this->post(
            sprintf('/v1/volumesets/%s/iops', $volumeSet->getKey()),
            [
                'san_id' => $this->san->getKey(),
                'max_iops' => 300,
            ],
            $this->validWriteHeaders
        )->assertStatus(204);
    }

    public function testSetIopsUnsuccessfully()
    {
        $this->storage->setAttribute('qos_enabled', false)->saveQuietly();
        $volumeSet = VolumeSet::factory()->create();
        $volumeSet->setAttribute('ucs_reseller_id', $this->solution->getKey())
            ->saveQuietly();
        $this->storage
            ->setAttribute('server_id', $this->san->getKey())
            ->saveQuietly();

        $this->post(
            sprintf('/v1/volumesets/%s/iops', $volumeSet->getKey()),
            [
                'san_id' => $this->san->getKey(),
                'max_iops' => 300,
            ],
            $this->validWriteHeaders
        )->assertJsonFragment([
            'title' => 'Unprocessable Entity',
            'detail' => 'Unable to configure IOPS: QoS is not available on the SAN',
        ])->assertStatus(400);
    }

    public function testExportSuccessful()
    {
        $volumeSet = VolumeSet::factory()->create();

        app()->bind(ArtisanService::class, function () {
            $mock = \Mockery::mock(ArtisanService::class)
                ->makePartial();
            $mock->allows('exportVolumeSet')
                ->andReturnTrue();
            return $mock;
        });

        $this->post(
            sprintf('/v1/volumesets/%s/export', $volumeSet->getKey()),
            [
                'san_id' => $this->san->getKey(),
            ],
            $this->validWriteHeaders
        )->assertStatus(204);
    }

    public function testExportUnsuccessful()
    {
        $volumeSet = VolumeSet::factory()->create();

        app()->bind(ArtisanService::class, function () {
            $mock = \Mockery::mock(ArtisanService::class)
                ->makePartial();
            $mock->allows('exportVolumeSet')
                ->andReturnFalse();
            return $mock;
        });

        $this->post(
            sprintf('/v1/volumesets/%s/export', $volumeSet->getKey()),
            [
                'san_id' => $this->san->getKey(),
            ],
            $this->validWriteHeaders
        )->assertJsonFragment([
            'title' => 'Storage Network Exception',
            'detail' => 'Failed to export volume set to host set: ',
        ])->assertStatus(503);
    }

    public function testDeleteSuccessful()
    {
        $volumeSet = VolumeSet::factory()->create();

        app()->bind(ArtisanService::class, function () {
            $mock = \Mockery::mock(ArtisanService::class)
                ->makePartial();
            $mock->allows('getVolumeSet')
                ->andReturnUsing(function () {
                    $output = new \StdClass();
                    $output->name = 'DeleteMe';
                    return $output;
                });
            $mock->allows('deleteVolumeSet')
                ->andReturnTrue();
            return $mock;
        });

        $this->post(
            sprintf('/v1/volumesets/%s/delete', $volumeSet->getKey()),
            [
                'datastore_id' => (Datastore::factory()->create())->getKey(),
            ],
            $this->validWriteHeaders
        )->assertStatus(204);
    }

    public function testDeleteUnsuccessful()
    {
        $volumeSet = VolumeSet::factory()->create();

        app()->bind(ArtisanService::class, function () {
            $mock = \Mockery::mock(ArtisanService::class)
                ->makePartial();
            $mock->allows('getVolumeSet')
                ->andReturnUsing(function () {
                    $output = new \StdClass();
                    $output->name = 'DeleteMe';
                    return $output;
                });
            $mock->allows('deleteVolumeSet')
                ->andReturnFalse();
            return $mock;
        });

        $response = $this->post(
            sprintf('/v1/volumesets/%s/delete', $volumeSet->getKey()),
            [
                'datastore_id' => (Datastore::factory()->create())->getKey(),
            ],
            $this->validWriteHeaders
        )->assertJsonFragment([
            'title' => 'Storage Network Exception',
            'detail' => 'Failed to delete volume set: ',
        ])->assertStatus(503);
    }
}