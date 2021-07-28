<?php

namespace Tests\V2;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
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

        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testFormatOfAvailabilityZoneID()
    {
        $this->post('/v2/availability-zones', [
            'code' => 'MAN1',
            'name' => 'Manchester Zone 1',
            'datacentre_site_id' => 1,
            'region_id' => $this->region->id
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(201);

        $this->assertMatchesRegularExpression(
            $this->generateRegExp(AvailabilityZone::class),
            (json_decode($this->response->getContent()))->data->id
        );
    }

    public function testFormatOfRoutersId()
    {
        $this->post('/v2/routers', [
            'name' => 'Manchester Router 1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);

        $this->assertMatchesRegularExpression(
            $this->generateRegExp(Router::class),
            (json_decode($this->response->getContent()))->data->id
        );
    }

    public function testDevEnvironmentId()
    {
        App::shouldReceive('environment')->zeroOrMoreTimes()->andReturnUsing(function () {
            return 'local';
        });
        $region = factory(Region::class)->create();
        $this->assertMatchesRegularExpression(
            $this->generateDevRegExp(Region::class),
            $region->id
        );
    }

    public function testProductionEnvironmentId()
    {
        App::shouldReceive('environment')->zeroOrMoreTimes()->andReturnUsing(function () {
            return 'production';
        });
        $region = factory(Region::class)->create();
        $this->assertMatchesRegularExpression(
            $this->generateRegExp(Region::class),
            $region->id
        );
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
        return "/^".(new $model())->keyPrefix."\-[a-f0-9]{8}$/i";
    }

    public function generateDevRegExp($model): string
    {
        return "/^".(new $model())->keyPrefix."\-[a-f0-9]{8}\-dev$/i";
    }
}
