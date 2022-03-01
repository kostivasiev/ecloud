<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class NetworkRulePort extends Model implements Filterable, Sortable, Manageable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules;

    const ICMP_MESSAGE_TYPE_ECHO_REQUEST = 8;
    public string $keyPrefix = 'nrp';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'network_rule_id',
            'name',
            'protocol',
            'source',
            'destination',
        ];
        parent::__construct($attributes);
    }

    public function networkRule(): BelongsTo
    {
        return $this->belongsTo(NetworkRule::class);
    }

    public function getSourceAttribute()
    {
        return preg_replace('/\s+/', '', $this->attributes['source']);
    }

    public function getDestinationAttribute()
    {
        return preg_replace('/\s+/', '', $this->attributes['destination']);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }

        return $query
            ->whereHas('networkRule.networkPolicy.network.router.vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            })
            ->whereHas('networkRule.networkPolicy.network.router', function ($query) {
                $query->where('is_management', false);
            });
    }

    public function isManaged() :bool
    {
        return (bool) $this->networkRule->networkPolicy->router->isManaged();
    }

    public function isHidden(): bool
    {
        return $this->isManaged();
    }

    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('network_rule_id', Filter::$stringDefaults),
            $factory->create('protocol', Filter::$stringDefaults),
            $factory->create('source', Filter::$stringDefaults),
            $factory->create('destination', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('network_rule_id'),
            $factory->create('protocol'),
            $factory->create('source'),
            $factory->create('destination'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('name', 'asc'),
        ];
    }

    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'network_rule_id' => 'network_rule_id',
            'protocol' => 'protocol',
            'source' => 'source',
            'destination' => 'destination',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
