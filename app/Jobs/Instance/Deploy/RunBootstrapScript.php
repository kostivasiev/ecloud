<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RunBootstrapScript extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/334
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);


        if (empty($this->instance->deploy_data['user_script'])) {
            Log::info('RunBootstrapScript for ' . $this->instance->id . ', no data passed so nothing to do');
            return;
        }

        $guestAdminCredential = $this->instance->credentials()
            ->where('username', ($this->instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'RunBootstrapScript failed for ' . $this->instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $endpoint = ($this->instance->platform == 'Linux') ? 'linux/script' : 'windows/script';
        $this->instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/guest/' . $endpoint,
            [
                'json' => [
                    'encodedScript' => base64_encode($this->instance->deploy_data['user_script']),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
