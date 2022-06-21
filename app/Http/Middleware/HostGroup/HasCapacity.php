<?php

namespace App\Http\Middleware\HostGroup;

use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use Symfony\Component\HttpFoundation\Response;

class HasCapacity
{
    public int $capacityThreshold;

    public function __construct()
    {
        $this->capacityThreshold = config('hostgroup.capacity.threshold');
    }

    public function handle($request, \Closure $next)
    {
        if ($request->has('host_group_id')) {
            $hostGroup = HostGroup::forUser($request->user())->findOrFail($request->input('host_group_id'));
            $instance = Instance::forUser($request->user())->findOrFail($request->route('instanceId'));

            $capacity = $hostGroup->getAvailableCapacity();
            if ($capacity['cpu']['percentage'] > $this->capacityThreshold ||
                ((int)ceil((($capacity['ram']['used'] + $instance->ram_capacity) /
                        $capacity['ram']['capacity']) / 100)) > $this->capacityThreshold) {
                return response()->json([
                    'errors' => [
                        'title' => 'Conflict',
                        'details' => 'There are insufficient resources to migrate to this hostgroup.',
                        'status' => Response::HTTP_CONFLICT,
                    ]
                ], Response::HTTP_CONFLICT);
            }
        }

        return $next($request);
    }
}
