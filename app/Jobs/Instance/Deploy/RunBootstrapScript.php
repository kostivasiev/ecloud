<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class RunBootstrapScript extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/334
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);


        if (empty($this->data['user_script'])) {
            Log::info('RunBootstrapScript for ' . $this->data['instance_id'] . ', no data passed so nothing to do');
            return;
        }

        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);

        $guestAdminCredential = $instance->credentials()
            ->where('username', ($instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'RunBootstrapScript failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $endpoint = ($instance->platform == 'Linux') ? 'linux/script' : 'windows/script';
        $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/' . $endpoint,
            [
                'json' => [
                    'encodedScript' => base64_encode($this->data['user_script']),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
