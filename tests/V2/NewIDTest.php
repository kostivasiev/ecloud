<?php

namespace Tests\V2;

use App\Events\V2\Task\Created;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NewIDTest extends TestCase
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

        $this->region = Region::factory()->create();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);
        $this->router = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testFormatOfAvailabilityZoneID()
    {
        putenv('APP_ENV=local');
        $response = $this->post('/v2/availability-zones', [
            'code' => 'MAN1',
            'name' => 'Manchester Zone 1',
            'datacentre_site_id' => 1,
            'region_id' => $this->region->id
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(201);

        $this->assertMatchesRegularExpression(
            $this->generateRegExp(AvailabilityZone::class),
            (json_decode($response->getContent()))->data->id
        );
    }

    public function testFormatOfRoutersId()
    {
        Event::fake(Created::class);
        putenv('APP_ENV=local');
        $post = $this->post('/v2/routers', [
            'name' => 'Manchester Router 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);

        $this->assertMatchesRegularExpression(
            $this->generateRegExp(Router::class),
            (json_decode($post->getContent()))->data->id
        );
        Event::assertDispatched(Created::class);
    }

    public function testDevEnvironmentId()
    {
        Event::fake(Created::class);

        putenv('APP_ENV=local');

        $post = $this->post('/v2/routers', [
            'name' => 'Manchester Router 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);

        $this->assertMatchesRegularExpression(
            $this->generateRegExp(Router::class),
            (json_decode($post->getContent()))->data->id
        );

        Event::assertDispatched(Created::class);
    }

    public function testProductionEnvironmentId()
    {
        Event::fake(Created::class);
        putenv('APP_ENV=production');
        $post = $this->post('/v2/routers', [
            'name' => 'Manchester Router 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);

        $this->assertMatchesRegularExpression(
            $this->generateRegExp(Router::class),
            (json_decode($post->getContent()))->data->id
        );
        Event::assertDispatched(Created::class);
    }

    /**
     * Generates a regular expression based on the specified model's prefix
     *
     * @param $model
     *
     * @return string
     */
    public function generateRegExp($model): string
    {
        if (App::environment() === 'local') {
            return "/^".(new $model())->keyPrefix."\-[a-f0-9]{8}\-dev$/i";
        }
        return "/^".(new $model())->keyPrefix."\-[a-f0-9]{8}$/i";
    }
}
