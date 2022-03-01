<?php

namespace App\Providers;

use App\Models\V1\VirtualMachine;
use App\Services\Kingpin\V1\KingpinService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
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
        $this->app->bind('App\Services\Kingpin\V1\KingpinService', function ($app, $parameters) {

            $environment = null;

            /**
             * Load via dependency injection in a controller which has vmId in the route
             * Loads a KingpinService configured for that VM
             */

            $routeParams = $this->app['request']->route()[2];

            // Do we have a VM to get the config from in a route? (i.e does the route have vmId?
            if (in_array('vmId', array_keys($routeParams))) {
                $virtualMachineQuery = VirtualMachine::query();
                if (!empty($vmIds)) {
                    $virtualMachineQuery->where('servers_id', '=', $routeParams['vmId']);
                }

                $virtualMachine = $virtualMachineQuery->first();

                if ($virtualMachine !== false) {
                    $pod = $virtualMachine->getPod();
                    $environment = $virtualMachine->type();
                }
            }

            /**
             * Or
             * Load using
             * $kingpin = app()->makeWith('App\Services\Kingpin\V1\KingpinService', [$datacentre, $environment]);
             */

            if (count($parameters) > 0) {
                // First parameter is a datacentre object
                $pod = $parameters[0];

                // Kingpin environment, e.g. Public/Hybrid etc
                if (isset($parameters[1])) {
                    $environment = $parameters[1];
                }
            }

            /**
             * Load the service
             */

            if (!is_object($pod) || !is_a($pod, 'App\Models\V1\Pod')) {
                $log_message = 'Unable to create KingpinService: Invalid Pod Object';
                Log::error($log_message);
                throw new \Exception($log_message);
            }

            // Load the VCE server details / credentials for the Pod
            $serverDetail = $pod->vceServerDetails(KingpinService::KINGPIN_USER);

            if (!$serverDetail) {
                throw new \Exception('Unable to create KingpinService: Unable to load VCE server details');
            }

            if (empty($serverDetail->server_detail_pass)) {
                throw new \Exception('Unable to create KingpinService: Unable to load service credentials');
            }

            $serviceBaseUri = $pod->ucs_datacentre_vmware_api_url;

            if (!empty($serverDetail->server_detail_login_port)) {
                $serviceBaseUri .= ':' . $serverDetail->server_detail_login_port;
            }

            $requestClient = new Client([
                'base_uri' => $serviceBaseUri,
                'defaults' => [
                    'auth' => [KingpinService::KINGPIN_USER, $serverDetail->server_detail_pass ?? null]
                ],
                'verify' => app()->environment() === 'production',
            ]);

            return new KingpinService(
                $requestClient,
                $pod,
                $environment
            );
        });
    }
}
