<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Kingpin\V1\KingpinService;

use GuzzleHttp\Client;
use Log;
use App\Models\V1\VirtualMachine;

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

            $environment = null;

            /**
             * Load via dependency injection in a controller which has vm_id in the route
             * Loads a KingpinService configured for that VM
             */

            $routeParams = $this->app['request']->route()[2];

            // Do we have a VM to get the config from in a route? (i.e does the route have vm_id?
            if (in_array('vm_id', array_keys($routeParams))) {
                $virtualMachineQuery = VirtualMachine::query();
                if (!empty($vmIds)) {
                    $virtualMachineQuery->where('servers_id', '=', $routeParams['vm_id']);
                }

                $virtualMachine = $virtualMachineQuery->first();

                if ($virtualMachine !== false) {
                    $pod = $virtualMachine->getPod();
                    $environment = $virtualMachine->type();
                }
            }

            /**
             * Or
             * Load using $kingpin = app()->makeWith('App\Kingpin\V1\KingpinService', [$datacentre, $environment]);
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

            $serviceBaseUri = $pod->ucs_datacentre_vmware_api_url;

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
