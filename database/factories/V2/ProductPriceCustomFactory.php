<?php
namespace Database\Factories\V2;

use App\Models\V2\ProductPriceCustom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPriceCustomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductPriceCustom::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'product_price_custom_reseller_id' => 1,
            'product_price_custom_sale_price' => 0.09,
        ];
    }
}
