<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\Api\Resource\Property\IntProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class Datastore extends Model implements Filterable, Sortable
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reseller_lun';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'reseller_lun_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types
     *
     * @var array
     */
    protected $casts = [
        'reseller_lun_id' => 'integer',
        'reseller_lun_size_gb' => 'integer',
        'reseller_lun_friendly_name' => 'string',
        'reseller_lun_ucs_reseller_id' => 'integer',
        'reseller_lun_ucs_site_id' => 'integer',
    ];


    /**
     * Ditto configuration
     * ----------------------
     */


    /**
     * Fudge until ditto supports column aliases
     * @param $key
     * @return string
     */
    public function getAttribute($key)
    {
        if (array_key_exists($this->table . '_' . $key, $this->attributes) || $this->hasGetMutator($key)) {
            return $this->getAttributeValue($this->table . '_' . $key);
        }

        return $this->getRelationValue($key);
    }


    /**
     * Ditto maps raw database names to friendly names.
     * @return array
     */
    public function databaseNames()
    {
        return [
            'id' => 'reseller_lun_id',
            'name' => 'reseller_lun_friendly_name',
            'capacity' => 'reseller_lun_size_gb',
            'solution_id' => 'reseller_lun_ucs_reseller_id',
            'site_id' => 'reseller_lun_ucs_site_id',
        ];
    }

    /**
     * Ditto filtering configuration
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns($factory)
    {
        return [
            $factory->create('id', Filter::$primaryKeyDefaults),
            $factory->create('name', Filter::$stringDefaults),
        ];
    }


    /**
     * Ditto sorting configuration
     * @param SortFactory $factory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns($factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
        ];
    }

    /**
     * Ditto sorting
     * @param SortFactory $sortFactory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort($sortFactory)
    {
        return [
            $sortFactory->create('id', 'asc'),
        ];
    }


    /**
     * Resource package
     * Map request property to database field
     *
     * @return array
     */
    public function properties()
    {
        return [
            IdProperty::create('reseller_lun_id', 'id'),
            StringProperty::create('reseller_lun_friendly_name', 'name'),
            IntProperty::create('reseller_lun_size_gb', 'capacity'),
            IntProperty::create('reseller_lun_ucs_reseller_id', 'solution_id'),
            IntProperty::create('reseller_lun_ucs_site_id', 'site_id'),
        ];
    }

    /**
     * Mutate the reseller_lun_friendly_name attribute
     * @param $value
     * @return string
     */
    public function getResellerLunFriendlyNameAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        $name_parts  = explode('_', $this->name);
        $name_number = array_pop($name_parts);
        $name_number = is_numeric($name_number) ? $name_number : 1;

        return 'Datastore ' . ucwords(strtolower($this->lun_type)) . '-' . str_pad($name_number, 2, '0', STR_PAD_LEFT);
    }
}
