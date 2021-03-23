<?php

namespace Tests\V2\Host;

use App\Jobs\Conjurer\Host\DeleteServiceProfile;
use App\Jobs\Conjurer\Host\PowerOff;
use App\Jobs\Kingpin\Host\CheckExists;
use App\Jobs\Kingpin\Host\MaintenanceMode;
use App\Jobs\Kingpin\Host\RemoveFromHostGroup;
use Tests\Mocks\Traits\Host;
use Tests\TestCase;

class DeleteTest extends TestCase
{

    use Host;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCheckExistsSuccess()
    {
        $this->host();
        $this->checkExists()
            ->checkOnline();
        $job = app()->make(CheckExists::class, ['model' => $this->host()]);
        $this->assertNull($job->handle());
    }

    public function testGetHostSpecFails()
    {
        $this->host();
        $this->checkExists(true);

        $job = app()->make(CheckExists::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testCheckHostOnlineFails()
    {
        $this->host();
        $this->checkExists()
            ->checkOnline(true);

        $job = app()->make(CheckExists::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testMaintenanceModeSuccess()
    {
        $this->host();
        $this->checkExists()
            ->maintenanceModeOn();
        $job = app()->make(MaintenanceMode::class, ['model' => $this->host()]);
        $this->assertNull($job->handle());
    }

    public function testMaintenanceModeFails()
    {
        $this->host();
        $this->checkExists()
            ->maintenanceModeOn(true);
        $job = app()->make(MaintenanceMode::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testPowerOffSuccess()
    {
        $this->host();
        $this->checkExists()
            ->powerOff();
        $job = app()->make(PowerOff::class, ['model' => $this->host()]);
        $this->assertNull($job->handle());
    }

    public function testPowerOffFails()
    {
        $this->host();
        $this->checkExists()
            ->powerOff(true);
        $job = app()->make(PowerOff::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testRemoveFromHostGroupSuccess()
    {
        $this->host();
        $this->checkExists()
            ->removeFromHostGroup();
        $job = app()->make(RemoveFromHostGroup::class, ['model' => $this->host()]);
        $this->assertNull($job->handle());
    }

    public function testRemoveFromHostGroupFails()
    {
        $this->host();
        $this->checkExists()
            ->removeFromHostGroup(true);
        $job = app()->make(RemoveFromHostGroup::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testDeleteServiceProfileSuccess()
    {
        $this->host();
        $this->checkExists()
            ->deleteServiceProfile();
        $job = app()->make(DeleteServiceProfile::class, ['model' => $this->host()]);
        $this->assertNull($job->handle());
    }

    public function testDeleteServiceProfileFails()
    {
        $this->host();
        $this->checkExists()
            ->deleteServiceProfile(true);
        $job = app()->make(DeleteServiceProfile::class, ['model' => $this->host()]);
        $this->assertFalse($job->handle());
    }

    public function testDeleteHost()
    {
        $this->host();
        $this->deleteHostMocks();
        $this->host()->delete();
        $this->host()->refresh();
        $this->assertNotNull($this->host()->deleted_at);
    }
}