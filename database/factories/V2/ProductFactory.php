<?php
namespace Database\Factories\V2;

use App\Models\V2\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'product_name' => 'az-aaaaaaaa: vcpu-1',
            'product_category' => 'eCloud',
            'product_subcategory' => 'Compute',
            'product_supplier' => 'UKFast',
            'product_active' => 'Yes',
            'product_duration_type' => 'Hour',
            'product_cost_price' => 0.00
        ];
    }
}
