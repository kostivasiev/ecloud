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
    public string $now;

    public function __construct()
    {
        parent::__construct();
        $this->now = date('Y-m-d H:i:s', strtotime('now'));
    }

    public function handle()
    {
        OrchestratorConfig::doesntHave('orchestratorBuilds')
            ->where('deploy_on', '<=', $this->now)
            ->whereNotNull('deploy_on')
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
