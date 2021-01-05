<?php
namespace App\Listeners\V2\FirewallPolicy\FirewallRule;

use App\Events\V2\FirewallPolicy\Deleted;
use App\Models\V2\FirewallPolicy;
use Illuminate\Support\Facades\Log;

class Delete
{
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
