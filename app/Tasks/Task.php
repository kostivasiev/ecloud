<?php

namespace App\Tasks;

use GuzzleHttp\Exception\RequestException;
use Throwable;

abstract class Task
{
    abstract public function jobs();

    public function exceptionCallback()
    {
        return function (Throwable $e) {
            return ($e instanceof RequestException && $e->hasResponse()) ?
                $e->getResponse()->getBody()->getContents() :
                $e->getMessage();
        };
    }
}