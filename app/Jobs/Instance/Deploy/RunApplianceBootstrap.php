<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RunApplianceBootstrap extends Job
{
    use Batchable;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/333
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);

        $guestAdminCredential = $instance->credentials()
            ->where('username', ($instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'RunApplianceBootstrap failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        if ($instance->platform !== 'Linux') {
            Log::info('RunApplianceBootstrap for ' . $instance->id . ', nothing to do for non-Linux platforms');
            return;
        }

        $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/linux/script',
            [
                'json' => [
                    'encodedScript' => base64_encode(
                        (new \Mustache_Engine())->loadTemplate($instance->image->script_template)
                            ->render($this->data['image_data'])
                    ),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
