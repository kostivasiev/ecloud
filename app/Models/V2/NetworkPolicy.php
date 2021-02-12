<?php

namespace App\Models\V2;

use App\Events\V2\NetworkAcl\Updated;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class NetworkAcl
 * @package App\Models\V2
 * @method static NetworkPolicy findOrFail(string $aclPolicyId)
 * @method static NetworkPolicy forUser($user)
 */
class NetworkPolicy extends Model implements Filterable, Sortable
{
    use CustomKey, DefaultName, SoftDeletes, Syncable;

    public string $keyPrefix = 'np';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'network_id',
            'vpc_id',
            'name',
        ];
        parent::__construct($attributes);
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function network()
    {
        return $this->belongsTo(Network::class);
    }

    public function scopeForUser($query, $user)
    {
        if (!empty($user->resellerId)) {
            $query->whereHas('vpc', function ($query) use ($user) {
                $resellerId = filter_var($user->resellerId, FILTER_SANITIZE_NUMBER_INT);
                if (!empty($resellerId)) {
                    $query->where('reseller_id', '=', $resellerId);
                }
            });
        }
        return $query;
    }

    /**
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('network_id', Filter::$stringDefaults),
            $factory->create('vpc_id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('network_id'),
            $factory->create('vpc_id'),
            $factory->create('name'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('id', 'asc'),
        ];
    }

    public function databaseNames()
    {
        return [
            'id' => 'id',
            'network_id' => 'network_id',
            'vpc_id' => 'vpc_id',
            'name' => 'name',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
