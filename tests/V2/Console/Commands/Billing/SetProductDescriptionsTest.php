<?php
namespace Tests\V2\Console\Commands\Billing;

use App\Console\Commands\Billing\SetProductDescriptions;
use App\Models\V2\Product;
use Tests\TestCase;

class SetProductDescriptionsTest extends TestCase
{
    public function testSuccess()
    {
        $product = Product::factory()->create([
            'product_name' => $this->availabilityZone()->id . ': ram-1mb',
            'product_subcategory' => 'Compute',
        ]);

        $unaffectedProduct = Product::factory()->create([
            'product_name' => 'SOME OTHER PRODUCT',
        ]);

        $this->assertNull($product->product_description);
        $this->assertNull($unaffectedProduct->product_description);

        $command = \Mockery::mock(SetProductDescriptions::class)->makePartial();
        $command->allows('option')->with('test-run')->andReturnFalse();
        $command->allows('info')->withAnyArgs()->andReturnTrue();

        $command->handle();

        $product->refresh();
        $unaffectedProduct->refresh();

        $this->assertEquals('1GB RAM (upto 24GB)', $product->product_description);
        $this->assertNull($unaffectedProduct->product_description);
    }
}