<?php

namespace App\Rules\V2\FirewallRulePort;

use App\Models\V2\FirewallRulePort;
use App\Models\V2\NetworkRulePort;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class PortWithinExistingRange implements Rule
{
    public FirewallRulePort|NetworkRulePort $model;

    public string $parentKeyColumn;
    public string $parentId;
    public ?string $source;
    public ?string $destination;
    public ?string $protocol;

    public function __construct(string $class)
    {
        $this->model = new $class;
        $this->parentKeyColumn = match ($class) {
            NetworkRulePort::class => 'network_rule_id',
            default => 'firewall_rule_id',
        };

        $this->parentId = Request::input($this->parentKeyColumn, null);
        if (Request::method() == 'PATCH') {
            $this->parentId = match ($class) {
                NetworkRulePort::class => Request::route('networkRulePortId', null),
                default => Request::route('firewallRulePortId', null),
            };
        }

        $this->source = Request::input('source', null);
        $this->destination = Request::input('destination', null);
        $this->protocol = Request::input('protocol', null);
    }

    public function passes($attribute, $value)
    {
        // check it isn't a port range
        if (Str::contains($value, '-')) {
            return true;
        }

        $altAttribute = ($attribute == 'source') ? 'destination' : 'source';

        $query = $this->model->where([
            [$this->parentKeyColumn, '=', $this->parentId],
            [$attribute, '>=', $value],
            [$attribute, '<=', $value],
            [$altAttribute, '=', $this->{$altAttribute}],
        ]);

        dd(
            $query->toSql(),
            $query->getBindings()
        );

        return $this->model->where([
                [$this->parentKeyColumn, '=', $this->parentId],
                [$attribute, '>=', $value],
                [$attribute, '<=', $value],
                [$altAttribute, '=', $this->{$altAttribute}],
            ])->count() == 0;
    }

    public function message()
    {
        return ':attribute port exists within an existing range';
    }
}