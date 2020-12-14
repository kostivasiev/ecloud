<?php

namespace App\Models\V2;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    protected $connection = 'reseller';
    protected $table = 'product_price';
    protected $primaryKey = 'product_price_id';
    public $timestamps = false;

    public $casts = [
        'product_price_sale_price' => 'float'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
