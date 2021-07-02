<?php
namespace App\Console\Commands\Orchestrator;

use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScheduledDeploy extends Command
{
    protected $signature = 'orchestrator:deploy {--D|debug} {--T|test-run}';
    protected $description = 'Scheduled Orchestration Deployments';

    public \DateTimeZone $timeZone;
    public Carbon $startDate;
    public Carbon $endDate;

    public function __construct()
    {
        parent::__construct();
        $this->timeZone = new \DateTimeZone(config('app.timezone'));
        $this->endDate = Carbon::now($this->timeZone);
        $this->startDate = $this->endDate->subSeconds(59);
    }

    public function handle()
    {
        $this->info('Processing Orchestrations Start');
        OrchestratorConfig::doesntHave('orchestratorBuilds')
            ->whereBetween('deploy_on', [$this->startDate, $this->endDate])
            ->each(function ($orchestratorConfig) {
                $this->info('Deploying Config ' . $orchestratorConfig->id);
                if (!$this->option('test-run')) {
                    $orchestratorBuild = app()->make(OrchestratorBuild::class);
                    $orchestratorBuild->orchestratorConfig()->associate($orchestratorConfig);
                    $orchestratorBuild->syncSave();
                }
            });
        $this->info('Processing Orchestrations End');

        return Command::SUCCESS;
    }
}
