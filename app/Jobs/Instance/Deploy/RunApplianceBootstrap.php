<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\ImageParameter;
use App\Models\V2\Instance;
use App\Services\AccountsService;
use App\Services\V2\NsxService;
use App\Services\V2\PasswordService;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RunApplianceBootstrap extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/333
     */
    public function handle(AccountsService $accountsService)
    {
        $instance = $this->model;

        if ($this->model->platform !== 'Linux') {
            Log::info('RunApplianceBootstrap for ' . $instance->id . ', nothing to do for non-Linux platforms, skipping');
            return;
        }

        if (empty($this->model->image->script_template)) {
            Log::info('RunApplianceBootstrap for ' . $instance->id . ', no script template defined, skipping');
            return;
        }

        $guestAdminCredential = $instance->credentials()
            ->where('username', ($instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'RunApplianceBootstrap failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        $imageData = (!empty($instance->deploy_data['image_data'])) ? $instance->deploy_data['image_data'] : [];

        // Check metadata for plesk image
        $imageMetadata = $this->model->image->imageMetadata->pluck('key', 'value')->flip();
        if ($imageMetadata->get('ukfast.license.type') == 'plesk') {
            if (empty($imageData['plesk_admin_email_address'])) {
                // get email address
                $imageData['plesk_admin_email_address'] = $accountsService->getPrimaryContactEmail($this->model->getResellerId());
                $deployData['image_data'] = $imageData;
                $instance->setAttribute('deploy_data', $deployData)->saveQuietly();
            }
            if (!$instance->credentials()
                ->where('name', '=', 'plesk_admin_password')
                ->exists()) {
                $credential = app()->make(Credential::class);
                $credential->fill([
                    'name' => 'plesk_admin_password',
                    'username' => 'plesk_admin_password',
                    'password' => (new PasswordService())->generate(),
                ]);
                $credential->save();
                $instance->credentials()->save($credential);
            }
        }

        $instance->image->imageParameters
            ->filter(function ($value) {
                return $value->type == ImageParameter::TYPE_PASSWORD;
            })->each(function ($passwordParameter) use ($instance, &$imageData) {
                $credential = $instance->credentials()->where('username', $passwordParameter->key)->first();
                if ($credential) {
                    $imageData[$passwordParameter->key] = $credential->password;
                }
            });

        $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/guest/linux/script',
            [
                'json' => [
                    'encodedScript' => base64_encode(
                        (new \Mustache_Engine())->loadTemplate($this->model->image->script_template)
                            ->render($imageData)
                    ),
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );
    }
}
