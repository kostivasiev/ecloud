<?php

namespace App\Listeners\V2;

use App\Jobs\Job;
use App\Traits\V2\Syncable;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class JobExceptionHandler
{
    /** @var Job $job */
    protected $job;

    /** @var \Throwable $exception */
    protected $exception;

    /** @var string $connectionName */
    protected $connectionName;

    public function handle($event)
    {
//        $this->job = $event->job;
//        $this->exception = $event->exception;
//        $this->connectionName = $event->connectionName;
//
//        $data = [
//            'job' => [
//                'name' => $this->job->getName(),
//            ],
//            'connectionName' => $this->connectionName,
//            'exception' => $this->exception,
//        ];
//
//        if ($this->exception instanceof RequestException && $this->exception->hasResponse()) {
//            $data['request_exception'] = json_decode($this->exception->getResponse()->getBody()->getContents(), true);
//        }
//
//        // If the job has a "Model" then update it's sync state to failed
//        $command = unserialize($this->job->payload()['data']['command']);
//        if (isset($command->model)) {
//            $model = $command->model;
//            $data['job']['model_id'] = $model->id;
//            $uses = class_uses($model);
//            if (is_array($uses) && in_array(Syncable::class, $uses)) {
//                /** @var Syncable $model */
//                $reason = (isset($data['request_exception'])) ? $data['request_exception'] : $this->exception->getMessage();
//                $model->setSyncFailureReason($reason);
//                Log::error('Marked Syncable model ' . $model->id . ' as failed', $data);
//            }
//        }
//
//        Log::error('Job exception', $data);
//        $this->job->fail($this->exception);
    }
}
