<?php

namespace App\Rules\V2\FirewallRulePort;

use App\Models\V2\FirewallRulePort;
use App\Models\V2\NetworkRulePort;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Request;

class UniquePortRule implements Rule
{
    public FirewallRulePort|NetworkRulePort $model;

    public string $parentKeyColumn;
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
        $this->source = Request::input('source', null);
        $this->destination = Request::input('destination', null);
        $this->protocol = Request::input('protocol', null);
    }

    public function passes($attribute, $value)
    {
        return $this->model
            ->where([
                [$this->parentKeyColumn, '=', $value],
                ['protocol', '=', $this->protocol],
                ['source', '=', $this->source],
                ['destination', '=', $this->destination],
            ])->count() == 0;
    }

    public function message()
    {
        return 'This port configuration already exists';
    }
}
