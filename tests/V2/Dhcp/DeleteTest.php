<?php

namespace Tests\V2\Dhcp;

use App\Events\V2\Dhcp\Deleted;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    /** @var AvailabilityZone */
    protected $availabilityZone;
    /** @var Region */
    private $region;
    /** @var Vpc */
    private $vpc;
    /** @var Dhcp */
    private $dhcp;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        factory(Credential::class)->create([
            'name' => 'NSX',
            'resource_id' => $this->availabilityZone->id,
        ]);

        Model::withoutEvents(function() {
            $this->dhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone->id
            ]);
        });

        $this->nsxServiceMock()->shouldReceive('delete')
            ->andReturnUsing(function () {
                return new Response(204, [], '');
            });
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['results' => [['id' => 0]]]));
            });
    }

    public function testNoPermsIsDenied()
    {
        $this->delete('/v2/dhcps/' . $this->dhcp->id)
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])->assertResponseStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete('/v2/dhcps/x', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Dhcp with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        Event::fake();

        $this->delete('/v2/dhcps/' . $this->dhcp->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);
        $this->assertNotNull(Dhcp::withTrashed()->findOrFail($this->dhcp->id)->deleted_at);

        Event::assertDispatched(Deleted::class, function ($job) {
            return $job->model->id === $this->dhcp->id;
        });
    }
}
