<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RunApplianceBootstrap extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/333
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        if ($this->instance->platform !== 'Linux') {
            Log::info('RunApplianceBootstrap for ' . $this->instance->id . ', nothing to do for non-Linux platforms, skipping');
            return;
        }

        if (empty($this->instance->image->script_template)) {
            Log::info('RunApplianceBootstrap for ' . $this->instance->id . ', no script template defined, skipping');
            return;
        }

        $guestAdminCredential = $this->instance->credentials()
            ->where('username', ($this->instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'RunApplianceBootstrap failed for ' . $this->instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $this->instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/guest/linux/script',
            [
                'json' => [
                    'encodedScript' => base64_encode(
                        (new \Mustache_Engine())->loadTemplate($this->instance->image->script_template)
                            ->render($this->instance->deploy_data['image_data'])
                    ),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
