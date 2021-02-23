<?php

namespace Tests\V2\Vpc;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Dhcp;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Region */
    private $region;

    /** @var AvailabilityZone */
    private $availabilityZone;

    /** @var Vpc */
    private $vpc;

    /** @var Dhcp */
    private $dhcp;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->delete('/v2/vpcs/' . $this->vpc->id)->seeJson([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
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
        $this->delete('/v2/vpcs/' . $this->vpc->id, [], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testDeleteVpcWithResourcesFails()
    {
        factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);

        $this->delete('/v2/vpcs/' . $this->vpc->getKey(), [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Precondition Failed',
            'detail' => 'The specified resource has dependant relationships and cannot be deleted',
            'status' => 412,
        ])->assertResponseStatus(412);
    }

    public function testSuccessfulDelete()
    {
        $this->delete('/v2/vpcs/' . $this->vpc->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->assertNotNull(Vpc::withTrashed()->findOrFail($this->vpc->id)->deleted_at);
    }
}
