<?php

namespace App\Jobs\IpAddress;

use App\Exceptions\V2\IpAddressCreationException;
use App\Jobs\Job;
use App\Models\V2\Network;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\RunsScripts;
use App\Traits\V2\LoggableTaskJob;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AllocateIpToIpAddress extends Job
{
    use Batchable, LoggableTaskJob, RunsScripts;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $model = $this->task->resource;

        if ($model->ip_address) {
            Log::info(sprintf('Skipping allocation of IP for %s', $model->id));
            return;
        }

        $lock = Cache::lock("ip_address." . $model->network_id, 60);
        try {
            $lock->block(60);
            $network = Network::forUser(request()->user())->findOrFail($model->network_id);
            $ip = $network->getNextAvailableIp();
            $model->ip_address = $ip;
            return $model->syncSave();
        } catch (LockTimeoutException $e) {
            throw new IpAddressCreationException;
        } finally {
            $lock->release();
        }
    }
}
