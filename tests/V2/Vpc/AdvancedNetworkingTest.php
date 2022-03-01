<?php
namespace Tests\V2\Vpc;

use App\Events\V2\Task\Created;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class AdvancedNetworkingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->vpc()->advanced_networking = true;
        $this->vpc()->save();
    }

    /**
     * @test I request the vpc collection, the request completes, I can see if `advanced_networking` is enabled
     */
    public function testRequestTheCollection()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get(
            '/v2/vpcs'
        )->seeJson(
            [
                'advanced_networking' => true,
            ]
        )->assertResponseStatus(200);
    }

    /**
     * @test I request a vpc item, the request completes, I can see if `advanced_networking` is enabled
     */
    public function testRequestAnItem()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->get(
            '/v2/vpcs/' . $this->vpc()->id
        )->seeJson(
            [
                'advanced_networking' => true,
            ]
        )->assertResponseStatus(200);
    }

    /**
     * @test I create a VPC, I request advanced networking be enabled, a VPC is created with `advanced_networking` set to true
     */
    public function testCreateAVpcWithAdvancedNetworking()
    {
        Event::fake(Created::class);

        app()->bind(Vpc::class, function () {
            return factory(Vpc::class)->create([
                'id' => 'vpc-test2',
            ]);
        });
        $this->post(
            '/v2/vpcs',
            [
                'name' => 'CreateTest Name',
                'reseller_id' => 1,
                'region_id' => $this->region()->id,
                'advanced_networking' => true,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-reseller-id' => 1,
            ]
        )->assertResponseStatus(202);

        $vpc = Vpc::findOrFail(json_decode($this->response->getContent())->data->id);
        $this->assertTrue(is_bool($vpc->advanced_networking));
        $this->assertTrue($vpc->advanced_networking);
    }

    /**
     * @test I create a Router, the selected VPC has advanced networking enabled, a different Edge Node is used
     */
    public function testCreateARouterWithAdvancedNetworking()
    {
        $this->markTestSkipped('Not yet implemented - awaiting clarification on tag to use');
    }
}