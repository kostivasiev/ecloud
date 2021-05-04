<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RunApplianceBootstrap extends Job
{
    use Batchable, JobModel;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/333
     */
    public function handle()
    {
        if ($this->model->platform !== 'Linux') {
            Log::info('RunApplianceBootstrap for ' . $this->model->id . ', nothing to do for non-Linux platforms, skipping');
            return;
        }

        if (empty($this->model->image->script_template)) {
            Log::info('RunApplianceBootstrap for ' . $this->model->id . ', no script template defined, skipping');
            return;
        }

        $guestAdminCredential = $this->model->credentials()
            ->where('username', ($this->model->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'RunApplianceBootstrap failed for ' . $this->model->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $this->model->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/guest/linux/script',
            [
                'json' => [
                    'encodedScript' => base64_encode(
                        (new \Mustache_Engine())->loadTemplate($this->instance->image->script_template)
                            ->render($this->model->deploy_data['image_data'])
                    ),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );
    }
}
