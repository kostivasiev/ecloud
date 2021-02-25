<?php

namespace Tests\V2\Dhcp;

use App\Events\V2\Dhcp\Deleted;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\NsxService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

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
        $mockEncryptionServiceProvider = \Mockery::mock(EncryptionServiceProvider::class)
            ->shouldAllowMockingProtectedMethods();
        app()->bind('encrypter', function () use ($mockEncryptionServiceProvider) {
            $mockEncryptionServiceProvider->shouldReceive('encrypt')->andReturn('EnCrYpTeD-pAsSwOrD');
            $mockEncryptionServiceProvider->shouldReceive('decrypt')->andReturn('somepassword');
            return $mockEncryptionServiceProvider;
        });

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        factory(Credential::class)->create([
            'name' => 'NSX',
            'resource_id' => $this->availabilityZone->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->id
        ]);
        $nsxService = app()->makeWith(NsxService::class, [$this->availabilityZone]);
        $mockNsxService = \Mockery::mock($nsxService)->makePartial();
        app()->bind(NsxService::class, function () use ($mockNsxService) {
            $mockNsxService->shouldReceive('delete')
                ->andReturnUsing(function () {
                    return new Response(204, [], '');
                });
            $mockNsxService->shouldReceive('get')
                ->andReturnUsing(function () {
                    return new Response(200, [], json_encode(['results' => [['id' => 0]]]));
                });
            return $mockNsxService;
        });
    }

    public function testNoPermsIsDenied()
    {
        $this->delete('/v2/dhcps/' . $this->dhcp->getKey())
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
        $this->delete('/v2/dhcps/' . $this->dhcp->getKey(), [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->assertNotNull(Dhcp::withTrashed()->findOrFail($this->dhcp->getKey())->deleted_at);

        Event::assertDispatched(Deleted::class, function ($job) {
            return $job->model->id === $this->dhcp->getKey();
        });
    }
}
