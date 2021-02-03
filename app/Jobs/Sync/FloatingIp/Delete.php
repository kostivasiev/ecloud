<?php

namespace App\Jobs\Sync\FloatingIp;

use App\Jobs\Job;
use App\Jobs\Nsx\FloatingIp\UndeployCheck;
use App\Jobs\Nsx\Nat\Undeploy as NatUndeploy;
use App\Jobs\Nsx\Nat\UndeployCheck as NatUndeployCheck;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    /** @var FloatingIp */
    private $model;

    public function __construct(FloatingIp $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $jobs = [];
        $nats = Nat::where('source_id', $this->model->id)
            ->orWhere('destination_id', $this->model->id)
            ->orWhere('translated_id', $this->model->id)
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
        $jobs[] = new UndeployCheck($this->model);

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
