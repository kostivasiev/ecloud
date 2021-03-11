<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class PowerOffTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $instance;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id
        ]);

        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->id,
            'name' => 'GetTest Default',
        ]);

        $mockKingpinService = \Mockery::mock(new KingpinService(new Client()));
        $mockKingpinService->shouldReceive('delete')->withArgs(['/api/v2/vpc/' . $this->vpc->id . '/instance/' . $this->instance->id . '/power'])->andReturn(
            new Response(200)
        );

        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });
    }

    public function testPowerOff()
    {
        $mockKingpinService = \Mockery::mock();
        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            $mockKingpinService->shouldReceive('delete')
                ->withArgs(['/api/v2/vpc/' . $this->vpc->id . '/instance/' . $this->instance->id . '/power'])
                ->andReturn(
                    new Response(200)
                );
            return $mockKingpinService;
        });

        $this->put(
            '/v2/instances/' . $this->instance->id . '/power-off',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);
    }
}
