<?php

namespace App\Models\V2;

use Illuminate\Database\Eloquent\Model;

class ProductPriceCustom extends Model
{
    protected $connection = 'reseller';
    protected $table = 'product_price_custom';
    protected $primaryKey = 'product_price_custom_id';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->timestamps = false;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_price_custom_product_id');
    }
}
