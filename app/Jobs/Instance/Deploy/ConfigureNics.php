<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ConfigureNics extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/327
     */
    public function handle()
    {
        Log::info('Performing ConfigureNics for instance '. $this->data['instance_id']);

        $instance = Instance::findOrFail($this->data['instance_id']);
        $nsxClient = $instance->availabilityZone->nsxClient();

        $logMessage = 'ConfigureNics for instance ' . $instance->getKey() . ': ';


        $database = app('db')->connection('ecloud');
        $database->beginTransaction();

        $instanceNics = $instance->nics()
            ->whereNotNull('network_id')
            ->where('network_id', '!=', '')
            ->lockForUpdate()
            ->get();

        $nicsByNetwork = $instanceNics->groupBy('network_id');
        $nicsByNetwork->each(function($nics, $networkId) use ($nsxClient, $logMessage, $database) {
            $network = Network::findOrFail($networkId);
            $subnet = \IPLib\Range\Subnet::fromString($network->subnet_range);

            /**
             * Get DHCP static bindings to determine used IP addresses on the network
             * @see https://185.197.63.88/policy/api_includes/method_ListSegmentDhcpStaticBinding.html
             */
            try {
                $cursor = null;
                $assignedNsxIps = collect();
                do {
                    $response = $nsxClient->get('/policy/api/v1/infra/tier-1s/' . $network->router->getKey() . '/segments/' . $network->getKey() . '/dhcp-static-binding-configs?cursor=' . $cursor);
                    $response = json_decode($response->getBody()->getContents());
                    foreach ($response->results as $dhcpStaticBindingConfig) {
                        $assignedNsxIps->push($dhcpStaticBindingConfig->ip_address);
                    }
                    $cursor = $response->cursor ?? null;
                } while (!empty($cursor));
            } catch (GuzzleException $exception) {
                $error = $logMessage . 'Failed: ' . $exception->getResponse()->getBody()->getContents();
                Log::info($error);
                $this->fail(new \Exception($error));
                return;
            }


            //Loop over nics (skip if no network_id) get the next available Ip in the range,
            //Check the ip isn't assigned to any nics resources already

            $nics->each(function ($nic) use ($database) {

                if (!$nic->save()) {
                    $database->rollback();
                    Log::info($error);
                    $this->fail(new \Exception($error));
                    return;
                }


            });



            //check via NSX that the IP isn't in use.



            //Create dhcp lease for the ip to the nic's mac address on NSX
            //https://185.197.63.88/policy/api_includes/method_CreateOrReplaceSegmentDhcpStaticBinding.html
            //Update the nic resource with the IP address.




        });

        if (!$applianceParameter->save()) {
            $database->rollback();
            throw new DatabaseException(
                'Failed to save Appliance version. Invalid parameter \''.$parameter['name'].'\''
            );
        }


        $database->commit();
            exit(print_r(
                'tets'
            ));












        /**
         * We need to reserve the first 4 IPs of a range, and the last (for broadcast).
         */
    }
}
