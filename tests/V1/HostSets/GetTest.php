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
        factory(HostSet::class, $count)->create();

        $this->get('/v1/hostsets', $this->validWriteHeaders);

        $this->assertResponseStatus(200) && $this->seeJson([
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
        $item = (factory(HostSet::class, 1)->create())->first();

        $this->json('GET', '/v1/hostsets/' . $item->uuid, [], $this->validWriteHeaders)
            ->seeStatusCode(200)
            ->seeJson([
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
        $this->get('/v1/hostsets/abc', $this->validWriteHeaders);

        $this->assertResponseStatus(404);
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
        ]);

        $this->assertResponseStatus(401);
    }
}
