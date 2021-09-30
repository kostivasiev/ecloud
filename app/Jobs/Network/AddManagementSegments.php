<?php
namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class AddManagementSegments extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private Task $task;
    private Router $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        if (!empty($this->task->data['management_router_id']) && !empty($this->task->data['management_network_id'])) {
            // need to check that the router & network are up and running
            $managementRouter = Router::find($this->task->data['management_router_id']);
            $managementNetwork = Network::find($this->task->data['management_network_id']);
            if ($managementRouter && $managementNetwork) {
                $this->awaitSyncableResources([
                    $managementRouter->id,
                    $managementNetwork->id,
                ]);
            }
            if ($managementNetwork) {
                Log::info(get_class($this) . ' - Create Management Segments Start', [
                    'router_id' => $managementRouter->id,
                    'network_id' => $managementNetwork->id,
                ]);
                $dhcp = $managementRouter->vpc->dhcps()->where('availability_zone_id', $managementRouter->availability_zone_id)->first();
                if (empty($dhcp)) {
                    $this->fail(new Exception('Unable to locate VPC DHCP server for router availability zone'));
                    return;
                }

                $subnet = Subnet::fromString($managementRouter->vpc->advanced_networking ?
                    config('network.segment.advanced'):
                    config('network.segment.standard'));
                //The first address is the network identification and the last one is the broadcast, they cannot be used as regular addresses.
                $networkAddress = $subnet->getStartAddress();
                $gatewayAddress = $networkAddress->getNextAddress();
                $dhcpServerAddress = $gatewayAddress->getNextAddress();
                $message = 'Deploying Management Network: ' . $managementNetwork->id . ': ';
                Log::info($message . 'Gateway Address: ' . $gatewayAddress->toString() . '/' . $subnet->getNetworkPrefix());
                Log::info($message . 'DHCP Server Address: ' . $dhcpServerAddress->toString() . '/' . $subnet->getNetworkPrefix());
                Log::info($message . 'DHCP ID: ' . $dhcp->id);

                $managementRouter->availabilityZone->nsxService()->patch(
                    'policy/api/v1/infra/tier-1s/' . $managementRouter->id . '/segments/' . $managementNetwork->id,
                    [
                        'json' => [
                            'resource_type' => 'Segment',
                            'subnets' => [
                                [
                                    'gateway_address' => $gatewayAddress->toString() . '/' . $subnet->getNetworkPrefix(),
                                    'dhcp_config' => [
                                        'resource_type' => 'SegmentDhcpV4Config',
                                        'server_address' => $dhcpServerAddress->toString() . '/' . $subnet->getNetworkPrefix(),
                                        'lease_time' => config('defaults.network.subnets.dhcp_config.lease_time'),
                                        'dns_servers' => config('defaults.network.subnets.dhcp_config.dns_servers')
                                    ]
                                ]
                            ],
                            'domain_name' => config('defaults.network.domain_name'),
                            'dhcp_config_path' => '/infra/dhcp-server-configs/' . $dhcp->id,
                            'advanced_config' => [
                                'connectivity' => 'ON'
                            ],
                            'tags' => [
                                [
                                    'scope' => config('defaults.tag.scope'),
                                    'tag' => $managementRouter->vpc->id
                                ]
                            ]
                        ]
                    ]
                );

                Log::info(get_class($this) . ' - Create Management Segments End', [
                    'router_id' => $managementRouter->id,
                    'network_id' => $managementNetwork->id,
                ]);
            }
        }
    }
}
