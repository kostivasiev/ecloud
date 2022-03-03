<?php

namespace Tests\V1\VolumeSets;

use App\Models\V1\VolumeSet;
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
        VolumeSet::factory($count)->create();

        $this->get('/v1/volumesets', $this->validWriteHeaders)
            ->assertJsonFragment([
                'total' => $count,
                'count' => $count,
            ])->assertStatus(200);
    }

    /**
     * Test for valid item
     * @return void
     */
    public function testValidItem()
    {
        $item = (VolumeSet::factory(1)->create())->first();

        $this->json('GET', '/v1/volumesets/' . $item->uuid, [], $this->validWriteHeaders)
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $item->uuid,
                'name' => $item->name,
                'solution_id' => $item->ucs_reseller_id,
                'max_iops' => $item->max_iops,
            ]);
    }

    /**
     * Test for invalid item
     * @return void
     */
    public function testInvalidItem()
    {
        $this->get('/v1/volumesets/abc', $this->validWriteHeaders)
            ->assertStatus(404);
    }

    /**
     * Test unauthorised
     * @return void
     */
    public function testUnauthorised()
    {
        $this->get('/v1/volumesets/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(401);
    }
}
