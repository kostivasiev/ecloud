<?php

namespace App\Models\V2;

class ProductPriceCustom extends V1ModelWrapper
{
    protected $connection = 'reseller';
    protected $table = 'product_price_custom';
    protected $primaryKey = 'product_price_custom_id';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->timestamps = false;

        $this->casts = [
            'product_price_custom_sale_price' => 'float'
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_price_custom_product_id');
    }
}
