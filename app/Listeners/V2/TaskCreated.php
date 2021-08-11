<?php

namespace App\Listeners\V2;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class TaskCreated
{
    public function handle($event)
    {
        Log::debug(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if ($event->model->job) {
            Log::debug(get_class($this) . " : Dispatching job", ["job" => $event->model->job]);
            try {
                dispatch(new $event->model->job($event->model));
            } catch (\Throwable $e) {
                throw $e;
            }
        } else {
            Log::debug(get_class($this) . " : Skipping job dispatch, no job defined for task", ["job" => $event->model->job]);
        }

        Log::debug(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
