<?php

namespace Tests\V1\HostSets;

use App\Models\V1\HostSet;
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
        HostSet::factory($count)->create();

        $this->get('/v1/hostsets', $this->validWriteHeaders)
            ->assertStatus(200)
            ->assertJsonFragment([
                'total' => $count,
                'count' => $count,
            ]);
    }

    /**
     * Test for valid item
     * @return void
     */
    public function testValidItem()
    {
        $item = (HostSet::factory(1)->create())->first();

        $this->json('GET', '/v1/hostsets/' . $item->uuid, [], $this->validWriteHeaders)
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $item->uuid,
                'name' => $item->name,
                'solution_id' => $item->ucs_reseller_id
            ]);
    }

    /**
     * Test for invalid item
     * @return void
     */
    public function testInvalidItem()
    {
        $this->get('/v1/hostsets/abc', $this->validWriteHeaders)
            ->assertStatus(404);
    }

    /**
     * Test unauthorised
     * @return void
     */
    public function testUnauthorised()
    {
        $this->get('/v1/hostsets/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(401);
    }
}
