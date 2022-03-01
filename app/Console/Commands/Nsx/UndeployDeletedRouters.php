<?php

namespace App\Console\Commands\Nsx;

use App\Models\V2\Router;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\Command;

class UndeployDeletedRouters extends Command
{
    protected $signature = 'nsx:undeploy-deleted-routers';

    protected $description = 'Undeploy routers that have been deleted and left orphaned in NSX';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Router::onlyTrashed()->each(function ($router) {
            if (empty($router->availabilityZone)) {
                return true;
            }

            try {
                $nsxService = $router->availabilityZone->nsxService();
            } catch (\Exception $exception) {
                return true;
            }

            try {
                $nsxService->get('policy/api/v1/infra/tier-1s/' . $router->id);
            } catch (ClientException $exception) {
                $response = $exception->getResponse();
                if ($response->getStatusCode() == 404) {
                    return true;
                }
            }

            $this->cascadeDelete($router->availabilityZone->nsxService(), 'policy/api/v1/infra/tier-1s/' . $router->id);

            $this->info('Router ' . $router->id . ' Undeployed.');

            return true;
        });

        return Command::SUCCESS;
    }

    /**
     * Deletes a resource and all it's children from NSX
     * @param $nsxService
     * @param $resource
     */
    private function cascadeDelete($nsxService, $resource)
    {
        $deleted = false;
        do {
            $this->info('Deleting "' . $resource . '"');
            try {
                $nsxService->delete($resource);
                $deleted = true;
            } catch (ClientException $exception) {
                $response = $exception->getResponse();
                if ($response->getStatusCode() !== 400) {
                    throw $exception;
                }

                $json = json_decode($response->getBody()->getContents());
                if (!preg_match(
                    '/The object path=\[[^\]]+\] cannot be deleted as either it has children or it is being referenced by other objects path=\[[^\]]+\]/',
                    $json->error_message
                )) {
                    throw $exception;
                }

                $childPaths = preg_replace(
                    '/The object path=\[[^\]]+\] cannot be deleted as either it has children or it is being referenced by other objects path=\[([^\]]+)\]/',
                    '$1',
                    $json->error_message
                );

                $childPaths = explode(',', $childPaths);
                foreach ($childPaths as $childPath) {
                    $this->warn('Failed to delete due to dependant "' . $childPath . '"');
                }

                foreach ($childPaths as $childPath) {
                    $this->cascadeDelete($nsxService, 'policy/api/v1' . $childPath);
                }
            }
        } while (!$deleted);

        $this->info('Deleted "' . $resource . '"');
    }
}
