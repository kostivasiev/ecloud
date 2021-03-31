<?php

namespace App\Jobs\Sync\FloatingIp;

use App\Jobs\Job;
use App\Jobs\Nsx\FloatingIp\UndeployCheck;
use App\Jobs\Nsx\Nat\Undeploy as NatUndeploy;
use App\Jobs\Nsx\Nat\UndeployCheck as NatUndeployCheck;
use App\Models\V2\Nat;
use App\Models\V2\Sync;
use App\Traits\V2\SyncableBatch;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    use SyncableBatch;

    private Sync $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->sync->resource->id]);

        $jobs = [];
        $nats = Nat::where('source_id', $this->sync->resource->id)
            ->orWhere('destination_id', $this->sync->resource->id)
            ->orWhere('translated_id', $this->sync->resource->id)
            ->get()
            ->filter(function ($model) {
                return $model instanceof Nat;
            });
        $nats->each(function ($nat) use (&$jobs) {
            $jobs[] = new NatUndeploy($nat);
        });
        $nats->each(function ($nat) use (&$jobs) {
            $jobs[] = new NatUndeployCheck($nat);
        });
        $jobs[] = new UndeployCheck($this->sync->resource);

        $this->deleteSyncBatch(
            [
                $jobs,
            ]
        )->dispatch();

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->resource->id]);
    }
}
