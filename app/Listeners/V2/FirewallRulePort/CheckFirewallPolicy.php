<?php

namespace App\Listeners\V2\FirewallRulePort;

use App\Exceptions\SyncException;
use App\Models\V2\Sync;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckFirewallPolicy
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $model = $event->model;
        $lock = Cache::lock("sync." . $model->id, 60);
        try {
            $lock->block(60);

            if ($model->firewallRule->firewallPolicy->syncs()->count() == 1 && $model->firewallRule->firewallPolicy->sync->status === Sync::STATUS_FAILED) {
                Log::warning(get_class($this) . ' : Update blocked, resource has a single failed sync', ['resource_id' => $model->id]);
                return false;
            }

            if ($model->firewallRule->firewallPolicy->sync->status === Sync::STATUS_INPROGRESS) {
                Log::warning(get_class($this) . ' : Update blocked, resource has outstanding sync', ['resource_id' => $model->id]);
                return false;
            }
        } catch (LockTimeoutException $e) {
            Log::error(get_class($this) . ' : Delete blocked, cannot obtain sync lock', ['resource_id' => $model->id]);
            throw new SyncException("Cannot obtain sync lock");
        } finally {
            $lock->release();
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
