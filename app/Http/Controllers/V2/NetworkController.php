<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Network\CreateRequest;
use App\Http\Requests\V2\Network\UpdateRequest;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Resources\V2\NetworkResource;
use App\Services\V2\KingpinService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use UKFast\DB\Ditto\QueryTransformer;

/**
 * Class NetworkController
 * @package App\Http\Controllers\V2
 */
class NetworkController extends BaseController
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \UKFast\DB\Ditto\QueryTransformer $queryTransformer
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, QueryTransformer $queryTransformer)
    {
        $collection = Network::forUser($request->user);
        $queryTransformer->config(Network::class)
            ->transform($collection);

        return NetworkResource::collection($collection->paginate(
            $request->input('per_page', env('PAGINATION_LIMIT'))
        ));
    }

    /**
     * @param Request $request
     * @param string $networkId
     * @return \App\Resources\V2\NetworkResource
     */
    public function show(Request $request, string $networkId)
    {
        return new NetworkResource(
            Network::forUser($request->user)->findOrFail($networkId)
        );
    }

    /**
     * @param CreateRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CreateRequest $request)
    {

        exit('here');
//        $instance = Instance::findOrFail('i-3967e72c');
//
//        $logMessage = 'Deploy instance ' . $instance->getKey() . ' : ';
////      GET
//
//        $kingpinService = app()->make(KingpinService::class, [$instance->availabilityZone]);
//
//        // Group NIC's by subnet so we can compare with available IP's from subnet on NSX
//        $nicsByNetwork = $instance->nics()->whereNotNull('network_id')->where('network_id', '!=', '')->get()->groupBy('network_id');
//
//
//        $nicsByNetwork->each(function($nics, $networkId) use ($kingpinService) {
//            $network = $nics->first()->network;
//            $subnet = \IPLib\Range\Subnet::fromString($network->subnet_range);
//
//            exit(var_dump('/policy/api/v1/infra/tier-1s/' . $network->router->getKey() . '/segments/' . $network->getKey() . '/dhcp-static-binding-configs'));
//
//            try {
//                $response = $kingpinService->get('/policy/api/v1/infra/tier-1s/' . $network->router->getKey() . '/segments/' . $network->getKey() . '/dhcp-static-binding-configs');
//                if ($response->getStatusCode() != 200) {
//                    throw new \Exception('Invalid response status code: ' . $response->getStatusCode());
//                }
//
//                $response = json_decode($response->getBody()->getContents());
//                if (!$response) {
//                    throw new \Exception('Could not decode response');
//                }
//
//
//                exit(print_r(
//                    $response
//                ));
//
//
//
//            } catch (GuzzleException $exception) {
//                $error = $exception->getResponse()->getBody()->getContents();
//                exit(print_r(
//                    $error
//                ));
//            } catch (\Exception $exception) {
//                exit('e');
//                exit(print_r(
//                    $exception->getMessage()
//                ));
//            }
//        });
//
//
//
//
//
//            exit(print_r(
//                'tets'
//            ));
//
//            //$range = \IPLib\Range\Subnet::fromString($nic->network->subnet_range);
//exit('banana');


//        $subnetRange = "10.0.0.0/24";
//
//        $range = \IPLib\Range\Subnet::fromString($subnetRange);
//
//        //The first address is the network identification and the last one is the broadcast, they cannot be used as regular addresses.
//        // We also want to reserve the next 2 Ip's for future use.
//        $networkAddress = $range->getStartAddress();
//        $gatewayAddress = $networkAddress->getNextAddress();
//        $dhcpServer = $gatewayAddress->getNextAddress();
//
//        var_dump([
//            $gatewayAddress->toString() . '/' . $range->getNetworkPrefix(),
//            $dhcpServer->toString() . '/' . $range->getNetworkPrefix(),
//        ]);
//
//        $ip = $dhcpServer;
//        $reserved = 2;
//        $iterator = 0;
//
//        while ($ip = $ip->getNextAddress()) {
//            $iterator++;
//            if ($ip->toString() === $range->getEndAddress()->toString() || !$range->contains($ip)) {
//                break;
//            }
//            if ($iterator <= $reserved) {
//                var_dump('reserved ' . $iterator);
//                continue;
//            }
//            var_dump($ip->toString());
//        }

        $network = new Network($request->only([
            'router_id', 'name', 'subnet_range'
        ]));
        $network->save();
        $network->refresh();
        return $this->responseIdMeta($request, $network->getKey(), 201);
    }

    /**
     * @param UpdateRequest  $request
     * @param string $networkId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request, string $networkId)
    {
        $network = Network::forUser(app('request')->user)->findOrFail($networkId);
        $network->fill($request->only([
            'router_id', 'name', 'subnet_range'
        ]));
        $network->save();
        return $this->responseIdMeta($request, $network->getKey(), 200);
    }

    /**
     * @param Request $request
     * @param string $networkId
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request, string $networkId)
    {
        $network = Network::forUser($request->user)->findOrFail($networkId);
        $network->delete();
        return response()->json([], 204);
    }
}
