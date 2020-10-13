<?php

namespace App\Jobs\Instance\Linux;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use App\Services\V2\PasswordService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class CreateUser extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle(PasswordService $passwordService)
    {
        Log::info('Starting Linux Create User "' . $this->data['username'] . '" on instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($instance->vpc->id);

        $credential = app()->makeWith(Credential::class, [
            'name' => $this->data['username'],
            'resource_id' => $instance->id,
            'user' => $this->data['username'],
            'password' => $passwordService->generate(),
        ]);
        $credential->save();

        try {
            /** @var Response $response */
            $response = $instance->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/TODO',
                [
                    'json' => [
                        'platform' => $instance->platform,
                        'password' => $credential->password,
                        'hostname' => $instance->id,
                    ],
                ]
            );
            if ($response->getStatusCode() == 200) {
                Log::info('PrepareOsUsers finished successfully for instance ' . $instance->id);
                return;
            }
            $this->fail(new \Exception(
                'Failed PrepareOsUsers for ' . $instance->id . ', Kingpin status was ' . $response->getStatusCode()
            ));
            return;
        } catch (GuzzleException $exception) {
            $this->fail(new \Exception(
                'Failed PrepareOsUsers for ' . $instance->id . ' : ' . $exception->getResponse()->getBody()->getContents()
            ));
            return;
        }
    }
}
