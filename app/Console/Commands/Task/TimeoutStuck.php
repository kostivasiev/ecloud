<?php

namespace App\Console\Commands\Task;

use App\Models\V2\Task;
use App\Console\Commands\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TimeoutStuck extends Command
{
    protected $signature = 'task:timeout-stuck {--hours=12} {--test-run}';

    protected $description = 'Times out tasks stuck "in-progress"';

    public function handle()
    {
        $hours = $this->option('hours');
        $dryRun = $this->option('test-run');
        $tasks = Task::query()->whereNull("failure_reason")
                                ->where('completed', '=', '0')
                                ->where('updated_at', '<=', Carbon::now()->addHours(-$hours))
                                ->get();

        $success = true;
        foreach ($tasks as $task) {
            $success = false;
            if ($dryRun) {
                $this->info("[TEST RUN] Marking task {$task->id} as failed");
            } else {
                $msg = "Marking task {$task->id} as failed";
                Log::warning($msg, ['task_id' => $task->id, 'command' => 'task:timeout-stuck']);
                $this->info($msg);

                $task->failure_reason = 'Task timed out';
                $task->save();
            }
        }

        return $success ? Command::SUCCESS : Command::FAILURE;
    }
}
