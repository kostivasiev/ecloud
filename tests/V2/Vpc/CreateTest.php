<?php

namespace Tests\V2\Vpc;

use App\Events\V2\DhcpCreated;
use App\Events\V2\VpcCreated;
use App\Models\V2\Dhcp;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{

    use DatabaseMigrations;

    public function testNoPermsIsDenied()
    {
        $data = [
            'name' => 'Manchester DC',
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            []
        )
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNullRegionIsFailed()
    {
        $data = [
            'name' => 'CreateTest Name',
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The region id field is required',
                'status' => 422,
                'source' => 'region_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotScopedFails()
    {
        $data = [
            'name' => 'CreateTest Name',
            'reseller_id' => 1,
            'region_id' => $this->region()->id
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Bad Request',
                'detail' => 'Missing Reseller scope',
                'status' => 400,
            ])
            ->assertResponseStatus(400);
    }

    public function testNoAdminFailsWhenConsoleIsSet()
    {
        $data = [
            'name' => 'CreateTest Name',
            'reseller_id' => 1,
            'region_id' => $this->region()->id,
            'console_enabled' => true,
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Forbidden',
                'details' => 'Console access cannot be modified',
                'status' => 403
            ]
        )->assertResponseStatus(403);
    }

    public function testExceedMaxVpcLimit()
    {
        $counter = 1;
        factory(Vpc::class, config('defaults.vpc.max_count'))
            ->make([
                'reseller_id' => 1,
                'region_id' => $this->region()->id,
                'console_enabled' => true,
            ])
            ->each(function ($vpc) use (&$counter) {
                $vpc->id = 'vpc-test' . $counter;
                $vpc->name = 'TestVPC-' . $counter;
                $vpc->saveQuietly();
                $counter++;
            });
        $data = [
            'name' => 'CreateTest Name',
            'reseller_id' => 1,
            'region_id' => $this->region()->id,
            'console_enabled' => true,
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The maximum number of ' . config('defaults.vpc.max_count') . ' VPCs has been reached',
            ]
        )->assertResponseStatus(422);
    }
}
