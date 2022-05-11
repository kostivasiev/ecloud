<?php
namespace Database\Factories\V2;

use App\Models\V2\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductPrice::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'product_price_type' => 'Standard',
            'product_price_sale_price' => 0.1,
        ];
    }
}
