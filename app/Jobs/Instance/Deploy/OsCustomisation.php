<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Services\V2\PasswordService;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class OsCustomisation extends Job
{
    use Batchable, JobModel;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @param PasswordService $passwordService
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/331
     */
    public function handle(PasswordService $passwordService)
    {
        $username = ($this->model->platform == 'Linux') ? 'root' : 'graphite.rack';
        $credential = Credential::create([
            'name' => $username,
            'resource_id' => $this->model->id,
            'username' => $username,
            'port' => $this->model->platform == 'Linux' ? '2020' : '3389',
        ]);
        $credential->password = $passwordService->generate();
        $credential->save();

        $this->model->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/oscustomization',
            [
                'json' => [
                    'platform' => $this->model->platform,
                    'password' => $credential->password,
                    'hostname' => $this->model->id,
                ],
            ]
        );
    }
}
