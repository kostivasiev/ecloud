<?php

namespace App\Models\V2;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;


/**
 * Class Product
 * @package App\Models\V1
 */
class Product extends Model
{
    protected $connection = 'reseller';
    protected $table = 'product';
    protected $primaryKey = 'product_id';
    public $timestamps = false;

    const PRODUCT_CATEGORIES = [
        'Compute',
        'Networking',
        'Storage',
        'License'
    ];

    /**
     * Apply a scope/filter to ** ALL ** Queries using this model to only return eCloud v2 products
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(function (Builder $builder) {
            return $builder->whereIn('product_subcategory', self::PRODUCT_CATEGORIES);
        });
    }

    public function productPrice()
    {
        return $this->hasMany(ProductPrice::class, 'product_price_product_id');
    }

    public function getPriceAttribute()
    {
        $productPrice = $this->productPrice()->where('product_price_type', 'Standard')->first();
        return $productPrice ? $productPrice->product_price_sale_price : null;
    }

    public function getNameAttribute()
    {
        preg_match("/(az-\w+[^:])(:\s)(\S[^-]+)/", $this->attributes['product_name'], $matches);
        return str_replace(' ', '_', $matches[3]);
    }

    public function getAvailabilityZoneIdAttribute()
    {
        preg_match("/(az-\w+[^:])(:\s)(\S[^-]+)/", $this->attributes['product_name'], $matches);
        return $matches[1];
    }

    public function scopeForAvailabilityZone($query, AvailabilityZone $availabilityZone)
    {
        return $query->where('product_name', 'like', $availabilityZone->getKey() . '%');
    }
}
