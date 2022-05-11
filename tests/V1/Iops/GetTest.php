<?php

namespace Tests\V1\Iops;

use App\Models\V1\IopsTier;
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
        IopsTier::factory($count)->create();

        $this->get('/v1/iops', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200)
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
        $item = (IopsTier::factory(1)->create())->first();

        $this->json('GET', '/v1/iops/' . $item->uuid, [], $this->validWriteHeaders)
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $item->uuid,
                'name' => $item->name,
                'limit' => $item->max_iops,
            ]);
    }

    /**
     * Test for invalid item
     * @return void
     */
    public function testInvalidItem()
    {
        $this->get('/v1/iops/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }
}
