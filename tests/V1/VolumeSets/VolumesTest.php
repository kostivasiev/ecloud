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
        (Solution::factory()->create());
        (Pod::factory(1)->create([
            'ucs_datacentre_id' => 1
        ]));

        (Storage::factory()->create([
            'server_id' => 1,
            'ucs_datacentre_id' => 1,
        ]));
        (San::factory()->create([
            'servers_id' => 1
        ]));

        $volumeSet = (VolumeSet::factory()->create())->first();

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

        $this->getJson(
            '/v1/volumesets/' . $volumeSet->uuid . '/volumes',
            $this->validWriteHeaders
        )->assertJsonFragment([
            'data' => [
                'volumes' => [
                    'myMockVolume',
                ]
            ],
            'meta' => [],
        ])->assertStatus(200);
    }

    public function testSameVolumeSetFoundOnManySans()
    {
        (Solution::factory()->create());
        (Pod::factory()->create([
            'ucs_datacentre_id' => 1
        ]));

        (Storage::factory()->create([
            'server_id' => 1,
            'ucs_datacentre_id' => 1,
        ]));
        (San::factory()->create([
            'servers_id' => 1
        ]));

        (Storage::factory()->create([
            'server_id' => 2,
            'ucs_datacentre_id' => 1,
        ]));
        (San::factory()->create([
            'servers_id' => 2
        ]));

        $volumeSet = (VolumeSet::factory()->create())->first();

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
        $this->getJson(
            '/v1/volumesets/' . $volumeSet->uuid . '/volumes',
            $this->validWriteHeaders
        )->assertStatus(500);
    }
}
