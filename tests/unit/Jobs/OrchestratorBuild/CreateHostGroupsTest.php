<?php

namespace Tests\unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\CreateHostGroups;
use App\Models\V2\HostGroup;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\HostGroup\TransportNodeProfile;
use Tests\TestCase;

class CreateHostGroupsTest extends TestCase
{
    use TransportNodeProfile;

    protected OrchestratorBuild $orchestratorBuild;
    protected OrchestratorConfig $orchestratorConfig;
    protected HostGroup $hostGroup;

    protected array $orchestratorData = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->orchestratorData = [
            'vpcs' => [
                $this->vpc()->attributesToArray(),
            ],
            'hostgroups' => [
                [
                    'id' => 'hg-test',
                    'name' => 'hg-test',
                    'vpc_id' => $this->vpc()->id,
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'host_spec_id' => $this->hostSpec()->id,
                    'windows_enabled' => false,
                ],
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
                CreateHostGroups::class . ' : OrchestratorBuild does not contain any Hostgroups, skipping'
            )->andThrow(new \Exception('No Hostgroups'));

        (new CreateHostGroups($this->orchestratorBuild))->handle();
    }

    /** @test */
    public function hostgroupHasBeenProcessed()
    {
        $this->expectExceptionMessage('Hostgroup Initiated');
        Log::partialMock()
            ->expects('info')
            ->once()
            ->withSomeOfArgs(
                CreateHostGroups::class . ' : OrchestratorBuild hostgroup. 0 has already been initiated, skipping'
            )->andThrow(new \Exception('Hostgroup Initiated'));

        $this->orchestratorBuild->state = ['hostgroup' => ['0']];
        $this->orchestratorBuild->save();

        (new CreateHostGroups($this->orchestratorBuild))->handle();
    }

    /** @test */
    public function hostgroupIsCreated()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateHostGroups($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);
    }
}
