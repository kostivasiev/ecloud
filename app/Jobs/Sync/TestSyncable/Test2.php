<?php

namespace App\Jobs\Sync\TestSyncable;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\CapacityChange;
use App\Jobs\Kingpin\Volume\Deploy;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Jobs\Sync\Completed;
use App\Models\V2\Sync;
use App\Models\V2\TestSyncable;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Batchable;

class Test2 extends Job
{
    use Batchable;

    private $model;

    public function __construct(Sync $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        sleep(3);

        //throw new \Exception("oops test2 exception");

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
