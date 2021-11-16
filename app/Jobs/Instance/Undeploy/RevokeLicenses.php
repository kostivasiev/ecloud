<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Licenses\AdminClient;
use UKFast\SDK\Licenses\Entities\License;

class RevokeLicenses extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * Revoke licenses from the licenses API
     * See https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/licenses/-/blob/master/openapi.yaml
     */
    public function handle()
    {
        $instance = $this->model;

        $licensesAdminClient = app()->make(AdminClient::class);

        $licenses = collect($licensesAdminClient
            ->licenses()
            ->getAll(
                [
                    'owner_type' => 'ecloud_vpc',
                    'owner_id' => $instance->id,
                ]
            ));

        $licenses->each(function (License $license) use ($licensesAdminClient, $instance) {
            $licensesAdminClient->licenses()->revoke($license->id);
            Log::info(get_class($this) . ' : License ' . $license->id .' revoked from instance ' . $instance->id);
        });
    }
}
