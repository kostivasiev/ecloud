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
use UKFast\Api\Auth\Consumer;

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

        $this->region = Region::factory()->create();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id,
        ]);
        Credential::factory()->create([
            'name' => 'NSX',
            'resource_id' => $this->availabilityZone->id,
        ]);

        Model::withoutEvents(function () {
            $this->dhcp = Dhcp::factory()->create([
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
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])->assertStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete('/v2/dhcps/x', [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment([
            'title' => 'Not found',
            'detail' => 'No Dhcp with that ID was found',
            'status' => 404,
        ])->assertStatus(404);
    }

    public function testSuccessfulDelete()
    {
        Event::fake();

        $this->delete('/v2/dhcps/' . $this->dhcp->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);
        $this->assertNotNull(Dhcp::withTrashed()->findOrFail($this->dhcp->id)->deleted_at);

        Event::assertDispatched(Deleted::class, function ($job) {
            return $job->model->id === $this->dhcp->id;
        });
    }
}
