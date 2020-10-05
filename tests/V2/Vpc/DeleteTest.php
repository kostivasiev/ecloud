<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Region */
    private $region;

    /** @var Vpc */
    private $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->delete('/v2/vpcs/' . $this->vpc->getKey())->seeJson([
            'title' => 'Unauthorised',
            'detail' => 'Unauthorised',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete('/v2/vpcs/x', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testNonMatchingResellerIdFails()
    {
        $this->vpc->reseller_id = 3;
        $this->vpc->save();
        $this->delete('/v2/vpcs/' . $this->vpc->getKey(), [], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->delete('/v2/vpcs/' . $this->vpc->getKey(), [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->assertNotNull(Vpc::withTrashed()->findOrFail($this->vpc->getKey())->deleted_at);
    }
}
