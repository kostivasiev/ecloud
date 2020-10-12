<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use App\Services\V2\PasswordService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class PrepareOsUsers extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/330
     */
    public function handle(PasswordService $passwordService)
    {
        Log::info('Starting PrepareOsUsers for instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);

        // Create user accounts
        if ($instance->platform == 'Windows') {
            $accounts = collect([
                ['Administrator', $passwordService->generate()],
                ['graphite.rack', $passwordService->generate()],
                ['ukfast.support', $passwordService->generate()],
            ]);
        } else {
            $accounts = collect([
                ['root', $passwordService->generate()],
                ['graphiterack', $passwordService->generate()],
                ['ukfastsupport', $passwordService->generate()],
            ]);
        }
        $accounts->each(function ($username, $password) use ($instance) {
            app()->makeWith(Credential::class, [
                'name' => $username,
                'resource_id' => $instance->id,
                'user' => $username,
                'password' => $password,
            ]);
        });

        // TODO BELOW
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $credential = $instance->credentials()
            ->where('user', ($instance->platform == 'Linux') ? 'root' : 'administrator')
            ->firstOrFail();
        if (!$credential) {
            $this->fail(new \Exception('PrepareOsUsers failed for ' . $instance->id . ', no credentials found'));
            return;
        }

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
