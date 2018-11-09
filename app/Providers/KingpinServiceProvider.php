<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Kingpin\V1\KingpinService;

use App\Models\V1\UCSDatacentre;
use GuzzleHttp\Client;
use Log;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class KingpinServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Kingpin\V1\KingpinService', function ($app, $parameters) {

            if (count($parameters) < 1) {
                throw new \Exception('Required paramaters to connect to Kingpin service are not available');
            }

            $environment = null;

            // First paramater is a datacentre object
            $UCSDatacentre = $parameters[0];

            if (!is_object($UCSDatacentre) || get_class($UCSDatacentre) != 'App\Models\V1\UCSDatacentre') {
                $log_message = 'Unable to create KingpinService: Invalid datacentre';
                Log::error($log_message);
                throw new \Exception($log_message);
            }

            // Kingpin environment, e.g. Public/Hybrid etc
            if (isset($parameters[1])) {
                $environment = $parameters[1];
            }

            $serviceBaseUri = $UCSDatacentre->ucs_datacentre_vmware_api_url;
            if (!empty(env('VMWARE_API_PORT'))) {
                $serviceBaseUri .= ':' . env('VMWARE_API_PORT');
            }

            return new KingpinService(
                new Client(['base_uri' => $serviceBaseUri]),
                $environment
            );
        });
    }
}
