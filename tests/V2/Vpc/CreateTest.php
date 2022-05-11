<?php

namespace Tests\V2\Vpc;

use App\Events\V2\Task\Created;
use App\Jobs\Vpc\UpdateSupportEnabledBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Vpc;
use App\Support\Sync;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\Account\AdminClient;
use UKFast\Admin\Account\AdminCustomerClient;
use UKFast\Admin\Account\Entities\Customer;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
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
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertStatus(401);
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
            ->assertJsonFragment([
                'title' => 'Validation Error',
                'detail' => 'The region id field is required',
                'status' => 422,
                'source' => 'region_id'
            ])
            ->assertStatus(422);
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
            ->assertJsonFragment([
                'title' => 'Bad Request',
                'detail' => 'Missing Reseller scope',
                'status' => 400,
            ])
            ->assertStatus(400);
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
        )->assertJsonFragment(
            [
                'title' => 'Forbidden',
                'details' => 'Console access cannot be modified',
                'status' => 403
            ]
        )->assertStatus(403);
    }

    public function testExceedMaxVpcLimit()
    {
        config(['defaults.vpc.max_count' => 10]);
        $counter = 1;
        Vpc::factory((int) config('defaults.vpc.max_count'))
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
            'region_id' => $this->region()->id
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The maximum number of ' . config('defaults.vpc.max_count') . ' VPCs has been reached',
            ]
        )->assertStatus(422);
    }

    public function testSupportEnabled()
    {
        app()->bind(AdminClient::class, function () {
            $mockClient = \Mockery::mock(AdminClient::class)->makePartial();
            $mockCustomer = \Mockery::mock(AdminCustomerClient::class)->makePartial();

            $mockCustomer->shouldReceive('getById')
                ->andReturn(
                    new Customer([
                        'paymentMethod' => 'Invoice',
                    ])
                );
            $mockClient->shouldReceive('customers')->andReturn($mockCustomer);
            return $mockClient;
        });

        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(false));
        Event::fake(Created::class);

        app()->bind(Vpc::class, function () {
            return Vpc::factory()->create([
                'id' => 'vpc-test2'
            ]);
        });
        $this->post(
            '/v2/vpcs',
            [
                'name' => 'CreateTest Name',
                'reseller_id' => 1,
                'region_id' => $this->region()->id,
                'support_enabled' => true,
            ]
        )->assertStatus(202);

        $vpc = Vpc::findOrFail('vpc-test2');

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        $this->assertTrue($vpc->support_enabled);
    }
}
