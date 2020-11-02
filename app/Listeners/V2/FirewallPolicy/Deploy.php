<?php

namespace App\Listeners\V2\FirewallPolicy;

use App\Models\V2\FirewallRule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param \App\Events\V2\FirewallPolicy\Saved|\App\Events\V2\FirewallRule\Saved $event
     * @return void
     * @throws \Exception
     */
    public function handle($event)
    {
        $policy = $event->model;
        if ($policy instanceof FirewallRule) {
            $policy = $event->model->firewallPolicy;
        }

        if (!$policy) {
            $message = 'Deploy called with invalid policy';
            Log::error($message, [
                'event' => $event,
            ]);
            $this->fail(new \Exception($message));
        }

        dispatch(new \App\Jobs\FirewallPolicy\Deploy([
            'policy_id' => $policy->id,
        ]));
    }
}