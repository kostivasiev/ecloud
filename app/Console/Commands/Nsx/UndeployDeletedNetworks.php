<?php

namespace App\Console\Commands\Nsx;

use App\Models\V2\Network;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\Command;

class UndeployDeletedNetworks extends Command
{
    protected $signature = 'nsx:undeploy-deleted-networks';

    protected $description = 'Undeploy segments for deleted networks';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $deletedNetworks = Network::onlyTrashed()->get();

        $deletedNetworks->each(function ($network) {
            if (empty($network->router->availabilityZone)) {
                return true;
            }

            try {
                $network->router->availabilityZone->nsxService()->get(
                    'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id
                );
            } catch (ClientException $exception) {
                if ($exception->getCode() == 404) {
                    return true;
                }
            }

            $network->router->availabilityZone->nsxService()->delete(
                'policy/api/v1/infra/tier-1s/' . $network->router->id . '/segments/' . $network->id
            );

            $this->info('Network ' . $network->id . ' Undeployed.');
        });

        return Command::SUCCESS;
    }
}
