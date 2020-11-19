<?php
namespace App\Listeners\V2\FirewallPolicy\FirewallRule;

use App\Events\V2\FirewallPolicy\Deleted;
use App\Jobs\FirewallPolicy\DeleteFirewallRules;
use App\Models\V2\FirewallPolicy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Delete implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);
        $firewallPolicy = FirewallPolicy::withTrashed()->findOrFail($event->model->getKey());
        $firewallPolicy->firewallRules()->each(function ($rule) {
            $rule->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
