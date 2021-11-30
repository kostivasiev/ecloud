<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RegisterLicenses;
use App\Models\V2\ImageMetadata;
use GuzzleHttp\Psr7\Response;
use Database\Seeders\CpanelImageSeeder;
use Database\Seeders\PleskImageSeeder;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
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
            ->allows('key')
            ->withArgs([10])
            ->andReturns(new Key(
                [
                    'key' => 'plesk license key'
                ]
            ));

        $mockAdminPleskClient
            ->allows('requestLicense')
            ->withArgs([$this->instance()->id, 'ecloud', 'PLESK-12-VPS-WEB-HOST-1M'])
            ->andReturnUsing(function () {
                $mockSelfResponse = \Mockery::mock(SelfResponse::class)->makePartial();
                $mockSelfResponse->allows('getId')->andReturns(10);
                return $mockSelfResponse;
            });

        $mockAdminLicensesClient = \Mockery::mock(AdminClient::class);
        $mockAdminLicensesClient->allows('plesk')->andReturns($mockAdminPleskClient);
        $mockAdminLicensesClient->allows('licenses')->andReturns($mockAdminLicensesLicensesClient);
        $mockAdminLicensesClient->allows('setResellerId')->andReturns($mockAdminLicensesClient);

        app()->bind(AdminClient::class, function () use ($mockAdminLicensesClient) {
            return $mockAdminLicensesClient;
        });

        dispatch(new RegisterLicenses($this->instance()));

        $this->instance()->refresh();

        $this->assertEquals('plesk license key', $this->instance()->deploy_data['image_data']['plesk_key']);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testRegisterMsSqlLicense()
    {
        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.identifier',
            'value' => 'WINDOWS-2019-DATACENTER-MSSQL2019-STANDARD',
            'image_id' => $this->image()->id
        ]);

        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.type',
            'value' => 'mssql',
            'image_id' => $this->image()->id
        ]);

        $mockAdminLicensesClient = \Mockery::mock(AdminClient::class);
        $mockAdminLicensesClient->allows('setResellerId')->andReturnSelf();
        $mockAdminLicensesClient->allows('licenses')->andReturnSelf();
        $mockAdminLicensesClient->allows('createEntity')
            ->andReturnUsing(function () {
                $responseMock = \Mockery::mock(SelfResponse::class)->makePartial();
                $responseMock->allows('getId')->andReturns(111);
                return $responseMock;
            });

        app()->bind(AdminClient::class, function () use ($mockAdminLicensesClient) {
            return $mockAdminLicensesClient;
        });

        dispatch(new RegisterLicenses($this->instance()));

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
        $mockAdminLicensesClient->allows('cpanel->requestLicense')
            ->withArgs([$this->instance()->id, 'ecloud', '1.1.1.1', 21163])
            ->andReturnUsing(function () {
                return \Mockery::mock(SelfResponse::class)->makePartial();
            });
        $mockAdminLicensesClient->allows('setResellerId')->andReturns($mockAdminLicensesClient);

        app()->bind(AdminClient::class, function () use ($mockAdminLicensesClient) {
            return $mockAdminLicensesClient;
        });

        dispatch(new RegisterLicenses($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
