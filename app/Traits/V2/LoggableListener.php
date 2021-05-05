<?php
namespace App\Traits\V2;

trait LoggableListener
{
    use LoggableModelJob;

    public $event;

    public function resolveModelId()
    {
        return $this->event;
    }

    public function __call($method, $args)
    {
        if (!method_exists($this, $method)) {
            throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $method);
        }
        if ($method === 'handle') {
            $this->event = $args[0];
        }
        return call_user_func_array(array($this, $method), $args);
    }

    public function getLoggingData()
    {
        return [
            'event' => $this->resolveModelId(),
        ];
    }
}
