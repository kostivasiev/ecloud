<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Credential;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    protected $availability_zone;
    protected $region;
    protected $router;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        factory(Credential::class)->create([
            'name' => 'NSX',
            'resource_id' => $this->availability_zone->id,
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availability_zone->id,
        ]);

        $this->nsxServiceMock()->shouldReceive('delete')
            ->andReturnUsing(function () {
                return new Response(204, [], '');
            });
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['results' => [['id' => 0]]]));
            });
    }

    public function testSuccessfulDelete()
    {
        Event::fake();
        $this->assertNull($this->router->deleted_at);
        $this->delete('/v2/routers/' . $this->router->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(204);
        $this->router->refresh();
        $this->assertNotNull($this->router->deleted_at);
    }
}
