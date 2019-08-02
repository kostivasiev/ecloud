<?php

namespace App\Providers;

use App\Exceptions\V1\ServiceUnavailableException;
use App\Http\Controllers\V1\DatastoreController;
use App\Models\V1\Datastore;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\ServiceProvider;
use App\Services\Artisan\V1\ArtisanService;


use GuzzleHttp\Client;
use Log;
use App\Models\V1\VirtualMachine;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ArtisanServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Services\Artisan\V1\ArtisanService', function ($app, $parameters) {

            $environment = null;

            //san name
            //solution_id
            //server_detail_user
            //server_Detail_pass



            /**
             * Load via dependency injection in a controller which has datastore_id in the route
             * Loads a ArtisanService configured for that VM
             */

            $routeParams = $this->app['request']->route()[2];

            // Do we have a datastore to get the config from in a route? (i.e does the route have datastore_id?
            if (in_array('datastore_id', array_keys($routeParams))) {


                // From the datastore load reseller_lun_ucs_storage_id ->

                $datastore = DatastoreController::getDatastoreById($this->app['request'], $routeParams['datastore_id']);

                if (!is_object($datastore) || !is_a($datastore, 'App\Models\V1\Datastore')) {
                    $log_message = 'Unable to create ArtisanService: Invalid Datastore Object';
                    Log::error($log_message);
                    throw new \Exception($log_message);
                }


                /**
                 * Load the artisan API URL and credentials from the Pod
                 */

                $apiUrl = $datastore->pod->storageApiUrl();
                if (empty($apiUrl)) {
                    Log::error('Failed to load storage API URL');
                    throw new ServiceUnavailableException('Failed to load storage for datastore.');
                }

                $apiPassword = $datastore->pod->storageApiPassword();
                if (empty($apiPassword)) {
                    Log::error('Failed to load storage API password');
                    throw new ServiceUnavailableException('Failed to load storage for datastore.');
                }

                /**
                 * Load the Storage / SAN credentials
                 */
                try {
                    $sanPassword = $datastore->storage->password();
                } catch (ModelNotFoundException $exception) {
                    Log::error('Failed to load SAN password.');
                    throw new ServiceUnavailableException('Failed to load storage for datastore.');
                }



                //exit(print_r($datastore->storage->port()));


                //exit(print_r($datastore->storage->serverDetail()));


                // Use ucs_storage.datacentre_id to load the pod to get the storage api url and the API credentials from the vce server details for the Pod/datacentre API user 'artisanapi'

                //--

                // Use ucs_storage.server_id (SAN id) to load the server details password for the SAN
                //
                // Use ucs_storage.server_id to load the server netbios for the san name









            }

            /**
             * Or
             * Load using
             * $artisan = app()->makeWith('App\Services\Artisan\V1\ArtisanService', []);
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

            $serviceBaseUri = $pod->ucs_datacentre_storage_api_url;

//            if (!empty(env('VMWARE_API_PORT'))) {
//                $serviceBaseUri .= ':' . env('VMWARE_API_PORT');
//            }


            //
            //         $this->requestClient = $requestClient;
            //        $this->sanName = $sanName;
            //        $this->solutionId = $solutionId;
            //


            $artisanService = new ArtisanService(
                new Client(['base_uri' => $serviceBaseUri]),
                $sanName,
                $solutionId
            );

            $artisanService->setAPICredentials(
                env('ARTISAN_API_USER'),
                env('ARTISAN_API_PASS')
            );

            // Load API credentials
            $sanCredentials = $this->getSANCredentials($solution->solution_san_id);

            $artisanService->setSANCredentials(
                $sanCredentials->server_detail_user,
                $sanCredentials->server_detail_pass
            );


            return new ArtisanService(
                new Client(['base_uri' => $serviceBaseUri]),
                $pod,
                $environment
            );
        });
    }
}
