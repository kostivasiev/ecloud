<?php
namespace App\Console\Commands\Orchestrator;

use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduledDeploy extends Command
{
    protected $signature = 'orchestrator:deploy {--D|debug} {--T|test-run}';
    protected $description = 'Scheduled Orchestration Deployments';

    public \DateTimeZone $timeZone;
    public $startDate;
    public $endDate;

    public function __construct()
    {
        parent::__construct();
        $this->endDate = date('Y-m-d H:i:s', strtotime('now'));
        $this->startDate = date('Y-m-d H:i:s', strtotime('now - 59 seconds'));
    }

    public function handle()
    {
        OrchestratorConfig::doesntHave('orchestratorBuilds')
            ->whereNotNull('deploy_on')
            ->whereBetween('deploy_on', [$this->startDate, $this->endDate])
            ->each(function ($orchestratorConfig) {
                Log::info('Deploying Config ' . $orchestratorConfig->id);
                if (!$this->option('test-run')) {
                    $orchestratorBuild = app()->make(OrchestratorBuild::class);
                    $orchestratorBuild->orchestratorConfig()->associate($orchestratorConfig);
                    $orchestratorBuild->syncSave();
                }
            });

        return Command::SUCCESS;
    }
}
