<?php

namespace App\Rules\V2\FirewallRulePort;

use App\Models\V2\FirewallRulePort;
use App\Models\V2\NetworkRulePort;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Request;

abstract class BasePortRule implements Rule
{
    public FirewallRulePort|NetworkRulePort $model;

    public string $class;
    public string $parentKeyColumn;
    public ?string $parentId;
    public ?string $source;
    public ?string $destination;
    public ?string $protocol;

    public function __construct(string $class)
    {
        $this->class = $class;
        $this->model = new $class;
        $this->parentKeyColumn = match ($class) {
            NetworkRulePort::class => 'network_rule_id',
            default => 'firewall_rule_id',
        };
        $this->parentId = Request::input($this->parentKeyColumn, null);
        if (Request::method() == 'PATCH') {
            $this->parentId = match ($class) {
                NetworkRulePort::class => Request::route('networkRulePortId'),
                default => Request::route('firewallRulePortId')
            };
        }
        $this->source = Request::input('source', null);
        $this->destination = Request::input('destination', null);
        $this->protocol = Request::input('protocol', null);
    }
}
