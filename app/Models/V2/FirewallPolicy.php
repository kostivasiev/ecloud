<?php

namespace App\Models\V2;

use App\Events\V2\FirewallPolicy\Deleting;
use App\Events\V2\FirewallPolicy\Saved;
use App\Events\V2\FirewallPolicy\Saving;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class FirewallPolicy
 * @package App\Models\V2
 * @method static findOrFail(string $firewallPolicyId)
 * @method static forUser($request)
 */
class FirewallPolicy extends Model implements Filterable, Sortable, ResellerScopeable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'fwp';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'name',
            'sequence',
            'router_id',
        ];
        $this->casts = [
            'sequence' => 'integer'
        ];
        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->router->getResellerId();
    }

    public function firewallRules()
    {
        return $this->hasMany(FirewallRule::class);
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }

        $query->whereHas('router', function ($query) {
            $query->where('is_management', false);
        });

        return $query->whereHas('router.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$enumDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('sequence', Filter::$stringDefaults),
            $factory->create('router_id', Filter::$enumDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('sequence'),
            $factory->create('router_id'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('sequence'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'sequence' => 'sequence',
            'router_id' => 'router_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
