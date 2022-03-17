<?php

namespace App\Services\V2;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class NsxService
{
    // Gateway Policy
    public const GET_GATEWAY_POLICIES = '/policy/api/v1/infra/domains/default/gateway-policies/%s';
    public const PATCH_GATEWAY_POLICY = '/policy/api/v1/infra/domains/default/gateway-policies/%s';
    public const DELETE_GATEWAY_POLICY = '/policy/api/v1/infra/domains/default/gateway-policies/%s';
    public const GET_GATEWAY_POLICY_RULES = '/policy/api/v1/infra/domains/default/gateway-policies/%s/rules';
    public const DELETE_GATEWAY_POLICY_RULE = '/policy/api/v1/infra/domains/default/gateway-policies/%s/rules/%s';

    // Realised State
    public const GET_REALISED_STATE_GATEWAY_POLICY = '/policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/%s';

    /**
     * @var Client
     */
    private $client;

    private $functions = [
        'csvToArray' => 'csvToArray',
    ];

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function __call($name, $arguments)
    {
        if (app()->environment() === 'testing') {
            Log::error('Called NSX without a mock!', [$name, $arguments]);
            dd([
                'NSX Method' => $name,
                'NSX Arguments' => $arguments,
            ]);
        }
        return call_user_func_array([$this->client, $name], $arguments);
    }

    /**
     * Convert CSV string to array and remove white space
     * @param string $string
     * @return array
     */
    public function csvToArray(string $string) : array
    {
        return Str::of($string)->split('/[\s,]+/')->toArray();
    }
}
