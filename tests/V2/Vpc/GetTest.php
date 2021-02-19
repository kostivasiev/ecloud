<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
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
            'reseller_id' => 1
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->get('/v2/vpcs')->seeJson([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testGetCollectionAdmin()
    {
        $this->get('/v2/vpcs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->vpc->id,
            'name' => $this->vpc->name,
        ])->assertResponseStatus(200);
    }

    public function testGetCollectionResellerScope()
    {
        $this->get('/v2/vpcs', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->vpc->getKey(),
        ])->assertResponseStatus(200);




        $this->get('/v2/vpcs', [
            'X-consumer-custom-id' => '2-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->dontSeeJson([
            'id' => $this->vpc->getKey(),
        ])->assertResponseStatus(200);
    }

    public function testGetCollectionAdminResellerScope()
    {
        $vpc1 = factory(Vpc::class)->create([
            'reseller_id' => 1,
            'region_id' => $this->region->getKey(),
        ]);
        $vpc2 = factory(Vpc::class)->create([
            'reseller_id' => 2,
            'region_id' => $this->region->getKey(),
        ]);
        $this->get('/v2/vpcs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
            'X-Reseller-Id' => 1
        ])->dontSeeJson([
            'id' => $vpc2->getKey(),
        ])->seeJson([
            'id' => $vpc1->getKey(),
        ])->assertResponseStatus(200);
    }

    public function testNonMatchingResellerIdFails()
    {
        $this->vpc->reseller_id = 3;
        $this->vpc->save();

        $this->get('/v2/vpcs/' . $this->vpc->getKey(), [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/vpcs/' . $this->vpc->getKey(), [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->vpc->id,
            'name' => $this->vpc->name,
        ])->assertResponseStatus(200);
    }
}
