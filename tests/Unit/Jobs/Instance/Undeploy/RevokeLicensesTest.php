<?php

namespace Tests\Unit\Jobs\Instance\Undeploy;

use App\Jobs\Instance\Undeploy\RevokeLicenses;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\Licenses\AdminClient;
use UKFast\SDK\Licenses\Entities\License;

class RevokeLicensesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testRevokeLicenses()
    {
        $mockAdminLicensesClient = \Mockery::mock(AdminClient::class);
        $mockAdminLicensesClient->shouldReceive('licenses->getAll')
            ->once()
            ->andReturn([
                new License([
                    'id' => 123,
                    'owner_id' => 'i-test',
                    'owner_type' => 'ecloud',
                    'key_id' => 'PLSK1234.5678',
                    'license_type' => 'plesk',
                    'reseller_id' => 0
                ]),
                new License([
                    'id' => 321,
                    'owner_id' => 'i-test',
                    'owner_type' => 'ecloud',
                    'key_id' => 'PLSK1234.5678',
                    'license_type' => 'plesk',
                    'reseller_id' => 0
                ])
            ]);

        $mockAdminLicensesClient->shouldReceive('licenses->revoke')
            ->once()
            ->withArgs([123])
            ->andReturn(true);

        $mockAdminLicensesClient->shouldReceive('licenses->revoke')
            ->once()
            ->withArgs([321])
            ->andReturn(true);

        app()->bind(AdminClient::class, function () use ($mockAdminLicensesClient) {
            return $mockAdminLicensesClient;
        });

        dispatch(new RevokeLicenses($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoLicensesToRevokeSucceeds()
    {
        $mockAdminLicensesClient = \Mockery::mock(AdminClient::class);
        $mockAdminLicensesClient->shouldReceive('licenses->getAll')
            ->once()
            ->andReturn([]);

        app()->bind(AdminClient::class, function () use ($mockAdminLicensesClient) {
            return $mockAdminLicensesClient;
        });

        dispatch(new RevokeLicenses($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
