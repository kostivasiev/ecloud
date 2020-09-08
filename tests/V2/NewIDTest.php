<?php

namespace Tests\V2;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class NewIDTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testFormatOfAvailabilityZoneID()
    {
        $this->region = factory(Region::class)->create();

        $data = [
            'code'    => 'MAN1',
            'name'    => 'Manchester Zone 1',
            'datacentre_site_id' => $this->faker->randomDigit(),
            'region_id' => $this->region->getKey()
        ];
        $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);
        $this->assertRegExp(
            $this->generateRegExp(AvailabilityZone::class),
            (json_decode($this->response->getContent()))->data->id
        );
    }

    public function testFormatOfRoutersId()
    {
        $vpc = factory(Vpc::class)->create([
            'name' => 'Manchester DC',
        ]);

        $data = [
            'name'    => 'Manchester Router 1',
            'vpc_id' => $vpc->getKey()
        ];
        $this->post(
            '/v2/routers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);
        $this->assertRegExp(
            $this->generateRegExp(Router::class),
            (json_decode($this->response->getContent()))->data->id
        );
    }

    public function testFormatOfVirtualDatacentresId()
    {
        $this->region = factory(Region::class)->create();

        $data = [
            'name'    => 'Manchester DC',
            'region_id' => $this->region->getKey()
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )
            ->assertResponseStatus(201);
        $this->assertRegExp(
            $this->generateRegExp(Vpc::class),
            (json_decode($this->response->getContent()))->data->id
        );
    }

    /**
     * Generates a regular expression based on the specified model's prefix
     * @param $model
     * @return string
     */
    public function generateRegExp($model): string
    {
        return "/^" . (new $model())->keyPrefix . "\-[a-f0-9]{8}$/i";
    }
}
