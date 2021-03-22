<?php

namespace Tests\V2\Host;

use App\Jobs\Conjurer\Host\DeleteServiceProfile;
use App\Jobs\Conjurer\Host\PowerOff;
use App\Jobs\Kingpin\Host\CheckExists;
use App\Jobs\Kingpin\Host\MaintenanceMode;
use App\Jobs\Kingpin\Host\RemoveFromHostGroup;
use Tests\TestCase;
use Tests\V2\Host\Mocks\ArtisanMocks;
use Tests\V2\Host\Mocks\ConjurerMocks;
use Tests\V2\Host\Mocks\KingpinMocks;

class DeleteTest extends TestCase
{
    use ArtisanMocks, ConjurerMocks, KingpinMocks;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCheckExistsSuccess()
    {
        $this->conjurerCheckExistsMock()
            ->kingpinCheckOnlineMock();

        $job = app()->make(CheckExists::class, ['model' => $this->host()]);
        $this->assertNull($job->handle());
    }

    public function testGetHostSpecFails()
    {
        $this->conjurerCheckExistsMock(true);

        $job = app()->make(CheckExists::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testCheckHostOnlineFails()
    {
        $this->conjurerCheckExistsMock()
            ->kingpinCheckOnlineMock(true);

        $job = app()->make(CheckExists::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testMaintenanceModeSuccess()
    {
        $this->conjurerCheckExistsMock()
            ->kingpinMaintenanceModeOnMock();
        $job = app()->make(MaintenanceMode::class, ['model' => $this->host()]);
        $this->assertNull($job->handle());
    }

    public function testMaintenanceModeFails()
    {
        $this->conjurerCheckExistsMock()
            ->kingpinMaintenanceModeOnMock(true);
        $job = app()->make(MaintenanceMode::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testPowerOffSuccess()
    {
        $this->conjurerCheckExistsMock()
            ->conjurerPowerOffMock();
        $job = app()->make(PowerOff::class, ['model' => $this->host()]);
        $this->assertNull($job->handle());
    }

    public function testPowerOffFails()
    {
        $this->conjurerCheckExistsMock()
            ->conjurerPowerOffMock(true);
        $job = app()->make(PowerOff::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testRemoveFromHostGroupSuccess()
    {
        $this->conjurerCheckExistsMock()
            ->kingpinRemoveFromHostGroupMock();
        $job = app()->make(RemoveFromHostGroup::class, ['model' => $this->host()]);
        $this->assertNull($job->handle());
    }

    public function testRemoveFromHostGroupFails()
    {
        $this->conjurerCheckExistsMock()
            ->kingpinRemoveFromHostGroupMock(true);
        $job = app()->make(RemoveFromHostGroup::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testDeleteServiceProfileSuccess()
    {
        $this->conjurerCheckExistsMock()
            ->conjurerDeleteServiceProfileMock();
        $job = app()->make(DeleteServiceProfile::class, ['model' => $this->host()]);
        $this->assertNull($job->handle());
    }

    public function testDeleteServiceProfileFails()
    {
        $this->conjurerCheckExistsMock()
            ->conjurerDeleteServiceProfileMock(true);
        $job = app()->make(DeleteServiceProfile::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testDeleteHost()
    {
        $this->conjurerCheckExistsMock()
            ->kingpinCheckOnlineMock()
            ->kingpinMaintenanceModeOnMock()
            ->conjurerPowerOffMock()
            ->artisanRemoveHostfrom3ParMock() // @todo
            ->kingpinRemoveFromHostGroupMock()
            ->conjurerDeleteServiceProfileMock();
        $this->host()->delete();
        $this->host()->refresh();
        $this->assertNotNull($this->host()->deleted_at);
    }
}