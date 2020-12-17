<?php

namespace App\Models\V2;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    protected $connection = 'reseller';
    protected $table = 'product_price';
    protected $primaryKey = 'product_price_id';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->timestamps = false;

        $this->casts = [
            'product_price_sale_price' => 'float'
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_price_product_id');
    }
}
