<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use App\Services\V2\PasswordService;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class OsCustomisation extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param PasswordService $passwordService
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/331
     */
    public function handle(PasswordService $passwordService)
    {
        Log::info('Starting OsCustomisation for instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);

        $username = ($instance->platform == 'Linux') ? 'root' : 'graphite.rack';
        $credential = app()->makeWith(Credential::class, [
            'name' => $username,
            'resource_id' => $instance->id,
            'username' => $username,
        ]);
        $credential->password = $passwordService->generate();
        $credential->save();

        try {
            /** @var Response $response */
            $response = $instance->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/oscustomization',
                [
                    'json' => [
                        'platform' => $instance->platform,
                        'password' => $credential->password,
                        'hostname' => $instance->id,
                    ],
                ]
            );
            if ($response->getStatusCode() != 200) {
                $message = 'Failed OsCustomisation for ' . $instance->id;
                Log::error($message, ['response' => $response]);
                $this->fail(new \Exception($message));
                return;
            }
        } catch (GuzzleException $exception) {
            $message = 'Failed OsCustomisation for ' . $instance->id;
            Log::error($message, ['exception' => $exception]);
            $this->fail(new \Exception($message));
            return;
        }
    }
}
