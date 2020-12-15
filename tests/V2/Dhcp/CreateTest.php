<?php

namespace Tests\V2\Dhcp;

use App\Events\V2\Dhcp\Created;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

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
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->post('/v2/dhcps', [
            'vpc_id' => $this->vpc->id,
        ])->seeJson([
            'title' => 'Unauthorised',
            'detail' => 'Unauthorised',
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
        $this->vpc->reseller_id = 3;
        $this->vpc->save();
        $this->post('/v2/dhcps', [
            'vpc_id' => $this->vpc->getKey(),
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

    public function testValidDataSucceeds()
    {
        $this->post('/v2/dhcps', [
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availabilityZone->getKey(),
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(201);

        $dhcpId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson(['id' => $dhcpId]);

        Event::assertDispatched(Created::class, function ($job) use ($dhcpId) {
            return $job->model->id === $dhcpId;
        });
    }
}
