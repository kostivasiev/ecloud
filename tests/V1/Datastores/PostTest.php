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

class PostTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCanCreateDataStore()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 1,
        ]);
        /** @var Pod $pod */
        $pod = Pod::factory(1)->create()->first();
        $san = San::factory(1)->create(['servers_ecloud_ucs_reseller_id' => 1])->first();
        $storage = Storage::factory(1)->create()->first();

        $data = [
            'solution_id' => Solution::first()->ucs_reseller_id,
            'name' => 'MY DATASTORE',
            'type' => 'Hybrid',
            'capacity' => 1
        ];
        dd($this->post(
            '/v1/datastores',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->getContent());

        $this->post(
            '/v1/datastores',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(200)
            ->assertJsonFragment([
                'total' => 122,
            ]);
    }
}
