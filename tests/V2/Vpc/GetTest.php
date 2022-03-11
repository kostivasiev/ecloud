<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    /** @var Region */
    private $region;

    /** @var Vpc */
    private $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = Region::factory()->create();
        $this->vpc = Vpc::withoutEvents(function () {
            return Vpc::factory()->create([
                'id' => 'vpc-test',
                'region_id' => $this->region->id,
                'reseller_id' => 1
            ]);
        });
    }

    public function testNoPermsIsDenied()
    {
        $this->get('/v2/vpcs')
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])->assertStatus(401);
    }

    public function testGetCollectionAdmin()
    {
        $this->get('/v2/vpcs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->vpc->id,
            'name' => $this->vpc->name,
        ])->assertStatus(200);
    }

    public function testGetCollectionResellerScopeCanSeeVpc()
    {
        $this->get('/v2/vpcs', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->vpc->id,
        ])->assertStatus(200);
    }

    public function testGetCollectionResellerScopeCanNotSeeVpc()
    {
        $this->get('/v2/vpcs', [
            'X-consumer-custom-id' => '2-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonMissing([
            'id' => $this->vpc->id,
        ])->assertStatus(200);
    }


    public function testGetCollectionAdminResellerScope()
    {
        $vpc1 = Vpc::withoutEvents(function () {
            return Vpc::factory()->create([
                'id' => 'vpc-test1',
                'region_id' => $this->region->id,
                'reseller_id' => 1
            ]);
        });
        $vpc2 = Vpc::withoutEvents(function () {
            return Vpc::factory()->create([
                'id' => 'vpc-test2',
                'region_id' => $this->region->id,
                'reseller_id' => 2
            ]);
        });
        $this->get('/v2/vpcs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
            'X-Reseller-Id' => 1
        ])->assertJsonMissing([
            'id' => $vpc2->id,
        ])->assertJsonFragment([
            'id' => $vpc1->id,
        ])->assertStatus(200);
    }

    public function testNonMatchingResellerIdFails()
    {
        $this->vpc->reseller_id = 3;
        $this->vpc->saveQuietly();

        $this->get('/v2/vpcs/' . $this->vpc->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])->assertJsonFragment([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertStatus(404);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/vpcs/' . $this->vpc->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'id' => $this->vpc->id,
            'name' => $this->vpc->name,
        ])->assertStatus(200);
    }
}
