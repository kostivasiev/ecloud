<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RegisterLicenses;
use Database\Seeders\CpanelImageSeeder;
use Database\Seeders\PleskImageSeeder;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\Licenses\AdminClient;
use UKFast\Admin\Licenses\AdminLicensesClient;
use UKFast\Admin\Licenses\AdminPleskClient;
use UKFast\SDK\Licenses\Entities\Key;
use UKFast\SDK\SelfResponse;

class RegisterLicensesTest extends TestCase
{
    public function testRegisterPleskLicense()
    {
        (new PleskImageSeeder())->run();
        $this->instance()->setAttribute('image_id', 'img-plesk')->save();

        $mockAdminPleskClient = \Mockery::mock(AdminPleskClient::class)->makePartial();

        $mockAdminLicensesLicensesClient = \Mockery::mock(AdminLicensesClient::class)->makePartial();

        $mockAdminLicensesLicensesClient
            ->shouldReceive('key')
            ->withArgs([10])
            ->andReturn(new Key(
                [
                    'key' => 'plesk license key'
                ]
            ));

        $mockAdminPleskClient
            ->shouldReceive('requestLicense')
            ->withArgs([$this->instance()->id, 'ecloud', 'PLESK-12-VPS-WEB-HOST-1M'])
            ->andReturnUsing(function (){
                $mockSelfResponse = \Mockery::mock(SelfResponse::class)->makePartial();
                $mockSelfResponse->shouldReceive('getId')->andReturn(10);
                return $mockSelfResponse;
            });

        $mockAdminLicensesClient = \Mockery::mock(AdminClient::class);
        $mockAdminLicensesClient->shouldReceive('plesk')->andReturn($mockAdminPleskClient);
        $mockAdminLicensesClient->shouldReceive('licenses')->andReturn($mockAdminLicensesLicensesClient);
        $mockAdminLicensesClient->shouldReceive('setResellerId')->andReturn($mockAdminLicensesClient);

        app()->bind(AdminClient::class, function () use ($mockAdminLicensesClient) {
            return $mockAdminLicensesClient;
        });

        dispatch(new RegisterLicenses($this->instance()));

        $this->instance()->refresh();

        $this->assertEquals('plesk license key', $this->instance()->deploy_data['image_data']['plesk_key']);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoLicenseRequiredSkips()
    {
        dispatch(new RegisterLicenses($this->instance()));

        $this->instance()->refresh();

        $this->assertFalse(isset($this->instance()->deploy_data['image_data']['plesk_key']));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testRegisterCpanelLicense()
    {
        (new CpanelImageSeeder())->run();

        $this->instance()
            ->setAttribute('image_id', 'img-cpanel')
            ->setAttribute('deploy_data', [
                'floating_ip_id' => $this->floatingIp()->id
            ])
            ->save();

        $mockAdminLicensesClient = \Mockery::mock(AdminClient::class);
        $mockAdminLicensesClient->shouldReceive('cpanel->requestLicense')
            ->withArgs([$this->instance()->id, 'ecloud', '1.1.1.1', 21163])
            ->andReturnUsing(function (){
                return \Mockery::mock(SelfResponse::class)->makePartial();
            });
        $mockAdminLicensesClient->shouldReceive('setResellerId')->andReturn($mockAdminLicensesClient);

        app()->bind(AdminClient::class, function () use ($mockAdminLicensesClient) {
            return $mockAdminLicensesClient;
        });

        dispatch(new RegisterLicenses($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
