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
                $router->availabilityZone->nsxService()->get(
                    'policy/api/v1/infra/tier-1s/' . $router->id
                );
            } catch (ClientException $exception) {
                if ($exception->getCode() == 404) {
                    return true;
                }
            }

            $router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $router->id
            );

            $this->info('Network ' . $router->id . ' Undeployed.');
        });
    }
}
