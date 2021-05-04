<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RunBootstrapScript extends Job
{
    use Batchable, JobModel;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/334
     */
    public function handle()
    {
        if (empty($this->model->deploy_data['user_script'])) {
            Log::info('RunBootstrapScript for ' . $this->model->id . ', no data passed so nothing to do');
            return;
        }

        $guestAdminCredential = $this->model->credentials()
            ->where('username', ($this->model->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'RunBootstrapScript failed for ' . $this->model->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $endpoint = ($this->model->platform == 'Linux') ? 'linux/script' : 'windows/script';
        $this->model->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/guest/' . $endpoint,
            [
                'json' => [
                    'encodedScript' => base64_encode($this->model->deploy_data['user_script']),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );
    }
}
