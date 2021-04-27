<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Services\V2\PasswordService;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class OsCustomisation extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * @param PasswordService $passwordService
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/331
     */
    public function handle(PasswordService $passwordService)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $username = ($this->instance->platform == 'Linux') ? 'root' : 'graphite.rack';
        $credential = Credential::create([
            'name' => $username,
            'resource_id' => $this->instance->id,
            'username' => $username,
            'port' => $this->instance->platform == 'Linux' ? '2020' : '3389',
        ]);
        $credential->password = $passwordService->generate();
        $credential->save();

        $this->instance->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/oscustomization',
            [
                'json' => [
                    'platform' => $this->instance->platform,
                    'password' => $credential->password,
                    'hostname' => $this->instance->id,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
