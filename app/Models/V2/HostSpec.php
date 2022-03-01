<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class HostSpec
 * @package App\Models\V2
 */
class HostSpec extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName;

    public string $keyPrefix = 'hs';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'ucs_specification_name',
            'cpu_sockets',
            'cpu_type',
            'cpu_cores',
            'cpu_clock_speed',
            'ram_capacity',
        ]);

        $this->casts = [
            'cpu_sockets' => 'integer',
            'cpu_cores' => 'integer',
            'cpu_clock_speed' => 'integer',
            'ram_capacity' => 'integer',
        ];

        parent::__construct($attributes);
    }

    public function hostGroups()
    {
        return $this->hasMany(HostGroup::class);
    }

    public function availabilityZones()
    {
        return $this->belongsToMany(AvailabilityZone::class);
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory): array
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('cpu_sockets', Filter::$numericDefaults),
            $factory->create('cpu_type', Filter::$stringDefaults),
            $factory->create('cpu_cores', Filter::$numericDefaults),
            $factory->create('cpu_clock_speed', Filter::$numericDefaults),
            $factory->create('ram_capacity', Filter::$numericDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory): array
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('cpu_sockets'),
            $factory->create('cpu_type'),
            $factory->create('cpu_cores'),
            $factory->create('cpu_clock_speed'),
            $factory->create('ram_capacity'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory): array
    {
        return [
            $factory->create('created_at', 'desc'),
        ];
    }

    public function databaseNames(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'cpu_sockets' => 'cpu_sockets',
            'cpu_type' => 'cpu_type',
            'cpu_cores' => 'cpu_cores',
            'cpu_clock_speed' => 'cpu_clock_speed',
            'ram_capacity' => 'ram_capacity',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
