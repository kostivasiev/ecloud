<?php

namespace App\Models\V2;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class Product
 * @package App\Models\V1
 */
class Product extends V1ModelWrapper implements Filterable, Sortable
{
    protected $connection = 'reseller';
    protected $table = 'product';
    protected $primaryKey = 'product_id';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->timestamps = false;

        $this->fillable([
            'product_sales_product_id',
            'product_name',
            'product_category',
            'product_subcategory',
            'product_supplier',
            'product_active',
            'product_duration_type',
            'product_duration_length',
            'product_cost_currency',
            'product_cost_price',
        ]);
    }

    const PRODUCT_CATEGORIES = [
        'Compute',
        'Networking',
        'Storage',
        'License',
        'Support',
    ];

    protected $appends = [
        'name',
        'price',
        'rate',
        'availability_zone_id',
        'category'
    ];

    /**
     * Apply a scope/filter to ** ALL ** Queries using this model to only return eCloud v2 products
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(function (Builder $builder) {
            return $builder->whereIn('product_subcategory', self::PRODUCT_CATEGORIES)
                ->where('product_active', 'Yes')
                ->where('product_category', 'eCloud');
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productPrice()
    {
        return $this->hasMany(ProductPrice::class, 'product_price_product_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productPriceCustom()
    {
        return $this->hasMany(ProductPriceCustom::class, 'product_price_custom_product_id');
    }

    /**
     * Get the price for the product, taking into account custom pricing set up for a reseller.
     * @param int|null $resellerId
     * @return \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed|null
     */
    public function getPrice(int $resellerId = null)
    {
        if (!empty($resellerId)) {
            $productPriceCustom = $this->productPriceCustom()->where('product_price_custom_reseller_id', $resellerId)->first();
            if (!empty($productPriceCustom)) {
                return $productPriceCustom->product_price_custom_sale_price;
            }
        }

        $productPrice = $this->productPrice()->where('product_price_type', 'Standard')->first();

        return $productPrice ? $productPrice->product_price_sale_price : null;
    }

    /**
     * @return string|string[]
     */
    public function getNameAttribute()
    {
        preg_match("/az-\w+[^:]:\s?(?(?=hs-)(hs-\S[^-]+)|(\S[^-]+))/", $this->attributes['product_name'], $matches);
        return str_replace(' ', '_', array_pop($matches) ?? null);
    }

    /**
     * @return mixed|null
     */
    public function getAvailabilityZoneIdAttribute()
    {
        preg_match("/^az-\w+[^:]/", $this->attributes['product_name'], $matches);
        return $matches[0] ?? null;
    }

    public function getCategoryAttribute()
    {
        return $this->attributes['product_subcategory'];
    }

    public function getRateAttribute()
    {
        return $this->attributes['product_duration_type'];
    }

    public function scopeForAvailabilityZone($query, AvailabilityZone $availabilityZone)
    {
        return $query->where('product_name', 'like', $availabilityZone->id . '%');
    }

    public function scopeForRegion($query, Region $region)
    {
        foreach ($region->availabilityZones as $availabilityZone) {
            $query->orWhere('product_name', 'like', $availabilityZone->id . '%');
        }

        return $query;
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('category', Filter::$stringDefaults),
            $factory->create('availability_zone_id', Filter::$stringDefaults)
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
            $factory->create('category'),
            $factory->create('availability_zone_id')
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('name', 'asc'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id' => 'product_id',
            'name' => 'product_name', // computed from product_name
            'category' => 'product_subcategory',
            'availability_zone_id' => 'product_name' // computed from product_name
        ];
    }

    /**
     * Transform request query parameters (filters) to work for the computed properties of this resource.
     * @param Request $request
     * @return Request
     */
    public static function transformRequest(Request $request) : Request
    {
        if (!empty($request->query)) {
            foreach ($request->query() as $key => $val) {
                $parts = explode(':', $key);
                if ($parts[1] == 'eq') {
                    $request->query->remove($key);
                    $request->query->add([$parts[0] . ':lk' => '*' . $val . '*']);
                }
            }
        }
        return $request;
    }
}
