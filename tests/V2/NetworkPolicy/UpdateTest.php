<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkPolicy $networkPolicy;

    public function setUp(): void
    {
        parent::setUp();
        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => $this->network()->id,
        ]);
    }

    public function testUpdateResource()
    {
        $this->patch(
            '/v2/network-policies/np-test',
            [
                'name' => 'New Policy Name',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'network_policies',
            [
                'id' => 'np-test',
                'name' => 'New Policy Name',
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }
}