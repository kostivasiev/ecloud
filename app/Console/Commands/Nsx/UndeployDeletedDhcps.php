<?php
namespace App\Console\Commands\Nsx;

use App\Models\V2\Dhcp;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UndeployDeletedDhcps extends Command
{
    protected $signature = 'nsx:undeploy-deleted-dhcps';
    protected $description = 'Undeploy deleted dhcps to remove orphaned data';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Dhcp::onlyTrashed()->each(function ($dhcp) {
            if (empty($dhcp->availabilityZone)) {
                return true;
            }

            try {
                $nsxService = $dhcp->availabilityZone->nsxService();
            } catch (\Exception $exception) {
                return true;
            }

            $this->info('Starting Undeploy of Dhcp ' . $dhcp->id);

            try {
                $nsxService->get(
                    '/policy/api/v1/infra/dhcp-server-configs/' . $dhcp->id
                );
            } catch (ClientException $exception) {
                if ($exception->getCode() == 404) {
                    return true;
                }
            }

            try {
                $nsxService->delete('/policy/api/v1/infra/dhcp-server-configs/' . $dhcp->id);
                $this->info('Dhcp ' . $dhcp->id . ' Undeployed.');
            } catch (ClientException|RequestException $e) {
                Log::warning('Unable to delete DHCP server ' . $dhcp->id, [
                    'detail' => $e,
                ]);
                $this->info('Unable to delete ' . $dhcp->id);
            }
        });

        return Command::SUCCESS;
    }
}
