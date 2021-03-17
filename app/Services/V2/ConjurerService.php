<?php

namespace App\Services\V2;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

final class ConjurerService
{
    /**
     * @var Client
     */
    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function __call($name, $arguments)
    {
        if (app()->environment() === 'testing') {
            Log::error('Called Conjurer without a mock!', [$name, $arguments]);
            dd([
                'Conjurer Method' => $name,
                'Conjurer Arguments' => $arguments,
            ]);
        }
        return call_user_func_array([$this->client, $name], $arguments);
    }
}
