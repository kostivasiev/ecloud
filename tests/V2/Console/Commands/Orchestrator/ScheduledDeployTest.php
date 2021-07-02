<?php
namespace Tests\V2\Console\Commands\Orchestrator;

use App\Console\Commands\Orchestrator\ScheduledDeploy;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Carbon\Carbon;
use Tests\TestCase;

class ScheduledDeployTest extends TestCase
{

    protected $command;
    protected string $startDate;
    protected string $endDate;
    protected OrchestratorConfig $orchestratorConfig;

    protected $infoArgument;
    protected array $lineArgument;
    protected $lineArgumentItem;

    public function setUp(): void
    {
        parent::setUp();
        $this->startDate = '2021-07-01 13:39:00';
        $this->endDate = '2021-07-02 13:40:00';
        $this->lineArgument = [];

        $this->command = \Mockery::mock(ScheduledDeploy::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->command->startDate = Carbon::createFromTimeString($this->startDate);
        $this->command->endDate = Carbon::createFromTimeString($this->endDate);

        $this->command->shouldReceive('info')
            ->with(\Mockery::capture($this->infoArgument))->andReturnUsing(function () {
                $this->lineArgument[] = $this->infoArgument;
                return true;
            });
        $this->command->shouldReceive('option')->with('test-run')->andReturnTrue();

        app()->bind(OrchestratorBuild::class, function () {
            $orchestratorBuild = \Mockery::mock(OrchestratorBuild::class)->makePartial();
            $orchestratorBuild->shouldReceive('orchestratorConfig->associate')
                ->withAnyArgs()
                ->andReturnTrue();
            $orchestratorBuild->shouldReceive('syncSave')->andReturnTrue();
            return $orchestratorBuild;
        });
    }

    public function assertConfigProcessed($configId)
    {
        $configFound = false;
        foreach ($this->lineArgument as $lineArgument) {
            if (strpos($lineArgument, $configId) !== -1) {
                $configFound = true;
                break;
            }
        }
        $this->assertTrue($configFound);
    }

    public function testWithConfigs()
    {
        $orchestratorConfigs = factory(OrchestratorConfig::class, 3)->create([
            'deploy_on' => $this->startDate,
        ]);
        $this->command->handle();
        foreach ($orchestratorConfigs as $orchestratorConfig) {
            $this->assertConfigProcessed($orchestratorConfig->id);
        }
    }

    public function testWithConfigNotDue()
    {
        factory(OrchestratorConfig::class)->create([
            'deploy_on' => Carbon::createFromTimeString('2031-07-02 13:40:00')
        ]);
        $this->command->handle();
        $this->assertEquals('Processing Orchestrations Start', $this->lineArgument[0]);
        $this->assertEquals('Processing Orchestrations End', $this->lineArgument[1]);
    }

    public function testWithNoConfig()
    {
        $this->command->handle();
        $this->assertEquals('Processing Orchestrations Start', $this->lineArgument[0]);
        $this->assertEquals('Processing Orchestrations End', $this->lineArgument[1]);
    }
}