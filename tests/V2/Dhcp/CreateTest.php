<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class CreateTest extends TestCase
{
    /** @var Region */
    private $region;

    /** @var AvailabilityZone */
    private $availabilityZone;

    /** @var Vpc */
    private $vpc;

    /** @var Router */
    private $router;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->post('/v2/dhcps', [
            'vpc_id' => $this->vpc()->id,
        ])->seeJson([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testNullNameIsFailed()
    {
        $this->post('/v2/dhcps', [
            'vpc_id' => '',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The vpc id field is required',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertResponseStatus(422);
    }

    public function testNotOwnedVpcIsFailed()
    {
        Model::withoutEvents(function() {
            $this->vpc()->reseller_id = 3;
            $this->vpc()->save();

        });

        $this->post('/v2/dhcps', [
            'vpc_id' => $this->vpc()->id,
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The specified vpc id was not found',
            'status' => 422,
            'source' => 'vpc_id'
        ])->assertResponseStatus(422);
    }
}
