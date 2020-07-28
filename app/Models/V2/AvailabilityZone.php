<?php

namespace App\Models\V2;

use App\Traits\V2\UUIDHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Resource\Property\DateTimeProperty;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class AvailabilityZones
 * @package App\Models\V2
 * @method static findOrFail(string $zoneId)
 */
class AvailabilityZone extends Model implements Filterable, Sortable
{
    use UUIDHelper, SoftDeletes;

    public const KEY_PREFIX = 'avz';
    protected $connection = 'ecloud';
    protected $table = 'availability_zones';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'code', 'name', 'datacentre_site_id', 'is_public'];
    protected $casts = [
        'is_public' => 'boolean',
        'datacentre_site_id' => 'integer',
    ];

    public $incrementing = false;
    public $timestamps = true;

    /**
     * @param \UKFast\DB\Ditto\Factories\FilterFactory $factory
     * @return array|\UKFast\DB\Ditto\Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('code', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('datacentre_site_id', Filter::$numericDefaults),
            $factory->create('is_public', Filter::$numericDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults)
        ];
    }

    /**
     * @param \UKFast\DB\Ditto\Factories\SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('code'),
            $factory->create('name'),
            $factory->create('datacentre_site_id'),
            $factory->create('is_public'),
            $factory->create('created_at'),
            $factory->create('updated_at')
        ];
    }

    /**
     * @param \UKFast\DB\Ditto\Factories\SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('code', 'asc'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id'         => 'id',
            'code'       => 'code',
            'name'       => 'name',
            'datacentre_site_id'    => 'datacentre_site_id',
            'is_public'    => 'is_public',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function routers()
    {
        return $this->belongsToMany(Router::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function vpns()
    {
        return $this->hasMany(Vpn::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function networks()
    {
        return $this->hasMany(Network::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gateways()
    {
        return $this->hasMany(Gateway::class);
    }
}
