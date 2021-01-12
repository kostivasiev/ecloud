<?php

namespace Tests\V2\Instances;

use App\Events\V2\Instance\Deleted;
use App\Listeners\V2\Instance\Undeploy;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteEventTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected AvailabilityZone $availability_zone;
    protected Instance $instance;
    protected Region $region;
    protected $volumes;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'GetTest Default',
        ]);
        $this->volumes = factory(Volume::class, 3)->make([
            'availability_zone_id' => $this->availability_zone->getKey(),
            'vpc_id' => $this->vpc->getKey(),
        ])
            ->each(function ($volume) {
                $volume->vmware_uuid = $this->faker->uuid;
                $volume->save();
                $volume->instances()->attach($this->instance);
            });

        $mockKingpinService = \Mockery::mock(new KingpinService(new Client()))->makePartial();
        $mockKingpinService->shouldReceive('delete')
            ->withArgs(['/api/v2/vpc/' . $this->instance->vpc->getKey() . '/instance/' . $this->instance->getKey() . '/power'])
            ->andReturn(
                new Response(200)
            );
        $mockKingpinService->shouldReceive('delete')
            ->withArgs(['/api/v2/vpc/' . $this->instance->vpc->getKey() . '/instance/' . $this->instance->getKey()])
            ->andReturn(
                new Response(200)
            );

        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });
    }
}
