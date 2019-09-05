<?php

namespace App\Providers;

use App\Exceptions\V1\ServiceUnavailableException;
use App\Http\Controllers\V1\DatastoreController;
use App\Models\V1\Datastore;
use App\Models\V1\San;
use App\Models\V1\Solution;
use App\Models\V1\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\ServiceProvider;
use App\Services\Artisan\V1\ArtisanService;

use GuzzleHttp\Client;
use Log;

/**
 * Load the Artisan service
 *
 * - Via dependency injection when an endpoint has datastore_id in the path
 *
 * - or by specifying a datastore object
 * $artisan = app()->makeWith('App\Services\Artisan\V1\ArtisanService', [['datastore' => $datastore]]);
 *
 * - or Load from Solution + San (using Solution's Pod)
 * $artisan = app()->makeWith('App\Services\Artisan\V1\ArtisanService', [['solution'=>$solution, 'san' => $san]);
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class ArtisanServiceProvider extends ServiceProvider
{
    /**
     * Register Artisan services.
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Services\Artisan\V1\ArtisanService', function ($app, $parameters) {

            /**
             * Load via dependency injection in a controller which has datastore_id in the route
             */
            $routeParams = $this->app['request']->route()[2];

            // Do we have a datastore to get the config from in a route? (i.e does the route have datastore_id?
            if (in_array('datastore_id', array_keys($routeParams))) {
                $datastore = DatastoreController::getDatastoreById($this->app['request'], $routeParams['datastore_id']);
                $config = $this->loadConfigFromDatastore($datastore);

                return $this->launchArtisanService($config);
            }


            if (count($parameters) > 0) {
                /**
                 * Load using a datastore object
                 */
                if (!empty($parameters[0]['datastore'])) {
                    $datastore = $parameters[0]['datastore'];
                    if (!is_object($datastore) || !is_a($datastore, Datastore::class)) {
                        $log_message = 'Unable to create ArtisanService: Invalid datastore Object';
                        Log::error($log_message);
                        throw new \Exception($log_message);
                    }
                    $config = $this->loadConfigFromDatastore($datastore);

                    return $this->launchArtisanService($config);
                }

                /**
                 * Load from Solution + San (using Solution's Pod) or Solution + Pod + San
                 */
                if (!empty($parameters[0]['solution']) && !empty($parameters[0]['san'])) {
                    $solution = $parameters[0]['solution'];
                    $san = $parameters[0]['san'];

                    if (!is_object($solution) || !is_a($solution, Solution::class)) {
                        $log_message = 'Unable to create ArtisanService: Invalid Solution Object';
                        Log::error($log_message);
                        throw new \Exception($log_message);
                    }

                    if (!is_object($san) || !is_a($san, San::class)) {
                        $log_message = 'Unable to create ArtisanService: Invalid San Object';
                        throw new \Exception($log_message);
                    }

                    $config = $this->loadConfig($san->storage);
                    $config['solution_id'] = $solution->getKey();

                    return $this->launchArtisanService($config);
                }
            }
        });
    }

    /**
     * Load the artisan API URL and credentials from the datastore
     * @param Datastore $datastore
     * @return array
     * @throws ServiceUnavailableException
     */
    private function loadConfigFromDatastore(Datastore $datastore) : array
    {
        if ($datastore->storage->count() < 1) {
            throw new ServiceUnavailableException('No storage is configured for this datastore.');
        }

        $config = $this->loadConfig($datastore->storage);
        $config['solution_id'] = $datastore->reseller_lun_ucs_reseller_id;
        return $config;
    }

    /**
     * Load the Artisan config for the SAN
     * @param Storage $storage
     * @return array
     * @throws ServiceUnavailableException
     */
    private function loadConfig(Storage $storage) : array
    {
        $pod = $storage->pod;
        $san = $storage->san;

        $storageApiUrl = $pod->storageApiUrl();
        if (empty($storageApiUrl)) {
            Log::error(
                'Failed to load storage API URL for Pod.',
                [
                    'pod_id' => $pod->getKey()
                ]
            );
            throw new ServiceUnavailableException('Failed to load storage for datastore.');
        }

        $storageApiPassword = $pod->storageApiPassword();
        if (empty($storageApiPassword)) {
            Log::error(
                'Failed to load storage API password for Pod.',
                [
                    'pod_id'       => $pod->getKey(),
                    'vce_server_id'=> $pod->ucs_datacentre_vce_server_id
                ]
            );
            throw new ServiceUnavailableException('Failed to load storage for datastore.');
        }

        //We might not always have/need this
        $storageApiPort = $pod->storageApiPort();

        // SAN credentials
        if (empty($san->name())) {
            Log::error(
                'Failed to load SAN name from server record.',
                [
                    'server_id' => $san->getKey()
                ]
            );
            throw new ServiceUnavailableException('Failed to load storage for datastore');
        }

        try {
            $sanPassword = $san->password();
        } catch (ModelNotFoundException $exception) {
            Log::error(
                'Failed to load SAN password.',
                [
                    'server_id' => $san->getKey()
                ]
            );
            throw new ServiceUnavailableException('Failed to load storage for datastore');
        }

        return [
            'api_url'      => $storageApiUrl,
            'api_password' => $storageApiPassword,
            'api_port'     => $storageApiPort,
            'san_name'     => $san->name(),
            'san_password' => $sanPassword
        ];
    }

    /**
     * Load the artisan service using the supplied config
     * @param $config
     * @return ArtisanService
     */
    private function launchArtisanService($config)
    {
        $serviceBaseUri = $config['api_url'];

        if (!empty($config['api_port'])) {
            $serviceBaseUri .= ':' . $config['api_port'];
        }

        return (new ArtisanService(
            new Client(['base_uri' => $serviceBaseUri]),
            $config['san_name'],
            $config['solution_id']
        ))
            ->setAPICredentials(
                ArtisanService::ARTISAN_API_USER,
                $config['api_password']
            )
            ->setSANCredentials(
                San::SAN_USERNAME,
                $config['san_password']
            );
    }
}
