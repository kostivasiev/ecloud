<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
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
        $this->patch('/v2/vpcs/' . $this->vpc->getKey(), [
            'name' => 'Manchester DC',
            'region_id' => $this->region->getKey(),
        ])->seeJson([
            'title' => 'Unauthorised',
            'detail' => 'Unauthorised',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testNullNameIsDenied()
    {
        $this->patch('/v2/vpcs/' . $this->vpc->getKey(), [
            'name' => '',
            'region_id' => $this->region->getKey(),
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The name field, when specified, cannot be null',
            'status' => 422,
            'source' => 'name'
        ])->assertResponseStatus(422);
    }

    public function testNullRegionIsDenied()
    {
        $this->patch('/v2/vpcs/' . $this->vpc->getKey(), [
            'name' => 'name',
            'region_id' => '',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The region id field, when specified, cannot be null',
            'status' => 422,
            'source' => 'region_id'
        ])->assertResponseStatus(422);
    }

    public function testNonMatchingResellerIdFails()
    {
        $this->vpc->reseller_id = 3;
        $this->vpc->save();
        $this->patch('/v2/vpcs/' . $this->vpc->getKey(), [
            'name' => 'Manchester DC',
            'region_id' => $this->region->getKey(),
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testValidDataIsSuccessful()
    {
        $data = [
            'name' => 'name',
            'reseller_id' => 2,
            'region_id' => $this->region->getKey(),
        ];
        $this->patch('/v2/vpcs/' . $this->vpc->getKey(), $data, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase('vpcs', $data, 'ecloud')
            ->assertResponseStatus(200);
        $this->assertEquals($data['name'], Vpc::findOrFail($this->vpc->getKey())->name);
    }
}
