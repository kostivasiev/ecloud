<?php

namespace App\Http\Controllers\V2;

use App\Http\Requests\V2\Network\CreateRequest;
use App\Http\Requests\V2\Network\UpdateRequest;
use App\Models\V2\Network;
use App\Resources\V2\NetworkResource;
use Illuminate\Http\Request;
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
