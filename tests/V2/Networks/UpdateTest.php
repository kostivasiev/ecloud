<?php

namespace Tests\V2\Networks;

use App\Models\V2\Networks;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testNoPermsIsDenied()
    {
        $net = $this->createNetwork();
        $data = [
            'name'    => 'Manchester Network',
        ];
        $this->patch(
            '/v2/networks/' . $net->getKey(),
            $data,
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNullNameIsDenied()
    {
        $net = $this->createNetwork();
        $data = [
            'name'    => '',
        ];
        $this->patch(
            '/v2/networks/' . $net->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The name field, when specified, cannot be null',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $net = $this->createNetwork();
        $data = [
            'name'    => 'Manchester Network',
        ];
        $this->patch(
            '/v2/networks/' . $net->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $networks = Networks::findOrFail($net->getKey());
        $this->assertEquals($data['name'], $networks->name);
    }

    /**
     * Create Network
     * @return \App\Models\V2\Networks
     */
    public function createNetwork(): Networks
    {
        $net = factory(Networks::class, 1)->create()->first();
        $net->save();
        $net->refresh();
        return $net;
    }

}