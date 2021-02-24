<?php

namespace App\Listeners\V2\NetworkRule;

use App\Events\V2\NetworkRule\Deleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Undeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     *
     * Patching the network policy does not remove the rule from NSX as expected,
     * so we're going to have to do it explicitly.
     *
     * @param Deleted $event
     * @return void
     */
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        $networkRule = $event->model;

        $networkRule->networkPolicy->network->router->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/domains/default/security-policies/' . $networkRule->networkPolicy->getKey() . '/rules/' . $networkRule->getKey()
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
