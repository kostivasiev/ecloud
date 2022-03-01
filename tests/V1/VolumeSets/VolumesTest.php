<?php

namespace Tests\V1\VolumeSets;

use App\Models\V1\Pod;
use App\Models\V1\San;
use App\Models\V1\Solution;
use App\Models\V1\Storage;
use App\Models\V1\VolumeSet;
use App\Services\Artisan\V1\ArtisanService;
use Mockery;
use Tests\V1\TestCase;

class VolumesTest extends TestCase
{
    public function testValidVolumeSet()
    {
        (factory(Solution::class, 1)->create());
        (factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 1
        ]));

        (factory(Storage::class, 1)->create([
            'server_id' => 1,
            'ucs_datacentre_id' => 1,
        ]));
        (factory(San::class, 1)->create([
            'servers_id' => 1
        ]));

        $volumeSet = (factory(VolumeSet::class, 1)->create())->first();

        app()->bind(ArtisanService::class, function () {
            return app()->instance(ArtisanService::class, Mockery::mock(ArtisanService::class, function ($mock) {
                $mock->shouldReceive('getVolumeSet')->andReturn((object)[
                    'name' => 'myMockVolumeSet',
                    'volumes' => [
                        'myMockVolume',
                    ],
                ]);
            }));
        });

        $this->json(
            'GET',
            '/v1/volumesets/' . $volumeSet->uuid . '/volumes',
            [],
            $this->validWriteHeaders
        )->seeJson([
            'data' => [
                'volumes' => [
                    'myMockVolume',
                ]
            ],
            'meta' => [],
        ])->seeStatusCode(200);
    }

    public function testSameVolumeSetFoundOnManySans()
    {
        (factory(Solution::class, 1)->create());
        (factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 1
        ]));

        (factory(Storage::class, 1)->create([
            'server_id' => 1,
            'ucs_datacentre_id' => 1,
        ]));
        (factory(San::class, 1)->create([
            'servers_id' => 1
        ]));

        (factory(Storage::class, 1)->create([
            'server_id' => 2,
            'ucs_datacentre_id' => 1,
        ]));
        (factory(San::class, 1)->create([
            'servers_id' => 2
        ]));

        $volumeSet = (factory(VolumeSet::class, 1)->create())->first();

        app()->bind(ArtisanService::class, function () {
            return app()->instance(ArtisanService::class, Mockery::mock(ArtisanService::class, function ($mock) {
                $mock->shouldReceive('getVolumeSet')->andReturn((object)[
                    'name' => 'myMockVolumeSet',
                    'volumes' => [
                        'myMockVolume',
                    ],
                ]);
            }));
        });
        $this->json(
            'GET',
            '/v1/volumesets/' . $volumeSet->uuid . '/volumes',
            [],
            $this->validWriteHeaders
        )->seeStatusCode(500);
    }
}
