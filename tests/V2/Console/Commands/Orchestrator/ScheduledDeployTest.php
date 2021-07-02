<?php
namespace Tests\V2\Console\Commands\Orchestrator;

use App\Console\Commands\Orchestrator\ScheduledDeploy;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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

    public function assertConfigProcessed($arguments, $configId)
    {
        $configFound = false;
        foreach ($arguments as $lineArgument) {
            if (strpos($lineArgument, $configId) !== -1) {
                $configFound = true;
                break;
            }
        }
        $this->assertTrue($configFound);
    }

    public function testWithConfigs()
    {
        $orchestratorConfig = factory(OrchestratorConfig::class)->create([
            'deploy_on' => $this->startDate,
        ]);
        Log::shouldReceive('info')->withSomeOfArgs('Processing Orchestrations Start');
        Log::shouldReceive('info')->withSomeOfArgs('Processing Orchestrations End');
        Log::shouldReceive('info')->withSomeOfArgs('Deploying Config ' . $orchestratorConfig->id);
        $this->assertEquals(0, $this->command->handle());
    }

    public function testWithConfigNotDue()
    {
        factory(OrchestratorConfig::class)->create([
            'deploy_on' => Carbon::createFromTimeString('2031-07-02 13:40:00')
        ]);
        Log::shouldReceive('info')->withSomeOfArgs('Processing Orchestrations Start');
        Log::shouldReceive('info')->withSomeOfArgs('Processing Orchestrations End');
        $this->assertEquals(0, $this->command->handle());
    }

    public function testWithNoConfig()
    {
        Log::shouldReceive('info')->withSomeOfArgs('Processing Orchestrations Start');
        Log::shouldReceive('info')->withSomeOfArgs('Processing Orchestrations End');
        $this->assertEquals(0, $this->command->handle());
    }
}