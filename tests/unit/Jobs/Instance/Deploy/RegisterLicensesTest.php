<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RegisterLicenses;
use App\Models\V2\ImageMetadata;
use GuzzleHttp\Psr7\Response;
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
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testRegisterPleskLicense()
    {
        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.identifier',
            'value' => 'PLESK-12-VPS-WEB-HOST-1M',
            'image_id' => $this->image()->id
        ]);

        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.type',
            'value' => 'plesk',
            'image_id' => $this->image()->id
        ]);

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

    public function testRegisterMsSqlLicense()
    {
        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.identifier',
            'value' => 'WINDOWS-2019-DATACENTER-MSSQL2019-STANDARD',
            'image_id' => $this->image()->id
        ]);

        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.type',
            'value' => 'MSSQL2019',
            'image_id' => $this->image()->id
        ]);

        $mockAdminPleskClient = \Mockery::mock(AdminPleskClient::class)->makePartial();
        $mockAdminPleskClient
            ->allows('post')
            ->withSomeOfArgs('v1/licenses')
            ->andReturnUsing(function () {
                return new Response(201, [], json_encode(['data' => [ 'id' => 'testid']]));
            });

        $mockAdminLicensesClient = \Mockery::mock(AdminClient::class);
//        $mockAdminLicensesClient->allows('plesk')->andReturns($mockAdminPleskClient);
//        $mockAdminLicensesClient->allows('licenses')->andReturns($mockAdminLicensesLicensesClient);
        $mockAdminLicensesClient->allows('setResellerId')->andReturns($mockAdminLicensesClient);

        app()->bind(AdminClient::class, function () use ($mockAdminLicensesClient) {
            return $mockAdminLicensesClient;
        });

        dispatch(new RegisterLicenses($this->instance()));

        $this->instance()->refresh();

        // needs reworking
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
}
