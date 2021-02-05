<?php

namespace App\Jobs\Volume;

use App\Events\V2\Volume\Saved;
use App\Jobs\Job;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class MarkSyncCompleted extends Job
{
    private Saved $event;

    public function __construct(Saved $event)
    {
        $this->event = $event;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['event' => $this->event]);

        /** @var Volume $volume */
        $volume = $this->event->model;
        $volume->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['event' => $this->event]);
    }
}
