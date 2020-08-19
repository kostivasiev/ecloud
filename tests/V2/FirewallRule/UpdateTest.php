<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\FirewallRule;
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

    public function testNullNameIsFailed()
    {
        $fwr = factory(FirewallRule::class)->create();
        $data = [
            'name' => '',
        ];
        $this->patch(
            '/v2/firewall-rules/' . $fwr->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
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

    public function testValidDataSucceeds()
    {
        $fwr = factory(FirewallRule::class)->create();
        $data = [
            'name' => 'Demo firewall rule 1',
        ];
        $this->patch(
            '/v2/firewall-rules/' . $fwr->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $availabilityZoneId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $availabilityZoneId,
        ]);
    }

}