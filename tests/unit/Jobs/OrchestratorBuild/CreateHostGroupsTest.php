<?php

namespace Tests\unit\Jobs\OrchestratorBuild;

use App\Jobs\OrchestratorBuild\CreateHostGroups;
use App\Models\V2\HostGroup;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\HostGroup\TransportNodeProfile;
use Tests\TestCase;
use Tests\unit\Jobs\OrchestratorBuild\Mocks\CreateHostGroupsMocks;

class CreateHostGroupsTest extends TestCase
{
    use TransportNodeProfile, CreateHostGroupsMocks;

    public const NUM_HOSTS = 4;

    protected CreateHostGroups $job;
    protected OrchestratorBuild $orchestratorBuild;
    protected OrchestratorConfig $orchestratorConfig;
    protected HostGroup $hostGroup;

    protected array $orchestratorData = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->hostGroup = factory(HostGroup::class)->make(
            [
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => false,
            ]
        );
        $hostGroupArray = $this->hostGroup->attributesToArray();
        $hostGroupArray['hosts'] = self::NUM_HOSTS;
        $this->orchestratorData = [
            'vpcs' => [
                $this->vpc()->attributesToArray(),
            ],
            'hostgroups' => [
                $hostGroupArray,
            ]
        ];
        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create(
            [
                'reseller_id' => 7052,
                'employee_id' => 1,
                'data' => json_encode($this->orchestratorData),
                'locked' => false,
            ]
        );
        $this->orchestratorBuild = factory(OrchestratorBuild::class)->create(
            [
                'orchestrator_config_id' => $this->orchestratorConfig->id,
                'state' => [],
            ]
        );
        $this->job = new CreateHostGroups($this->orchestratorBuild);
    }

    /** @test */
    public function thereAreNoHostgroupsDefined()
    {
        $this->expectExceptionMessage('No Hostgroups');

        unset($this->orchestratorData['hostgroups']);
        $this->orchestratorConfig->data = json_encode($this->orchestratorData);
        $this->orchestratorConfig->save();

        Log::partialMock()
            ->expects('info')
            ->once()
            ->withSomeOfArgs(
                get_class($this->job) . ' : OrchestratorBuild does not contain any Hostgroups, skipping'
            )->andThrow(new \Exception('No Hostgroups'));

        $this->job->handle();
    }

    /** @test */
    public function hostgroupHasBeenProcessed()
    {
        $this->expectExceptionMessage('Hostgroup Initiated');
        Log::partialMock()
            ->expects('info')
            ->once()
            ->withSomeOfArgs(
                get_class($this->job) . ' : OrchestratorBuild hostgroup. 0 has already been initiated, skipping'
            )->andThrow(new \Exception('Hostgroup Initiated'));

        $this->orchestratorBuild->state = ['hostgroup' => ['0']];
        $this->orchestratorBuild->save();

        $this->job->handle();
    }

    /** @test */
    public function hostgroupIsCreated()
    {
        $this->buildHostgroupIsCreatedMocks();

        $this->job->handle();

        $this->assertEquals($this->orchestratorBuild->state['hostgroup'][0], $this->hostGroup->id);
    }
}
