<?php

namespace App\Models\V2;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductPrice extends V1ModelWrapper
{
    use HasFactory;

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

        $this->fillable([
            'product_price_product_id',
            'product_price_type',
            'product_price_sale_price',
        ]);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_price_product_id');
    }
}
