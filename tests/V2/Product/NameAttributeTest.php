<?php

namespace Tests\V2\Product;

use App\Models\V2\HostSpec;
use App\Models\V2\Product;
use App\Models\V2\ResourceTier;
use Faker\Factory as Faker;
use Tests\TestCase;

class NameAttributeTest extends TestCase
{
    protected $faker;

    public string $rtPrefix;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $rt = ResourceTier::factory(['id' => 'rt-test-cpu', 'availability_zone_id' => $this->availabilityZone()->id])->create();
        $this->rtPrefix = $rt->keyPrefix;
        ResourceTier::factory(['id' => 'rt-testcpux', 'availability_zone_id' => $this->availabilityZone()->id])->create();
        HostSpec::factory(['id' => 'hs-testcpux'])->create();
        HostSpec::factory(['id' => 'hs-fail-cpu'])->create();
    }

    public function testReturnsFullRTName()
    {
        $name = "rt-test-cpu";
        $product = Product::factory(['product_name' => $this->availabilityZone()->id . ': ' . $name])->create();
        $this->assertEquals($product->name, $name);

        $name = "rt-testcpux";
        $product = Product::factory(['product_name' => $this->availabilityZone()->id . ': ' . $name])->create();
        $this->assertEquals($product->name, $name);
    }

    public function testReturnsRTDoesntExist()
    {
        $name = "rt-fake-cpu";
        $product = Product::factory(['product_name' => $this->availabilityZone()->id . ': ' . $name])->create();
        $this->assertEquals($product->name, $this->rtPrefix);

        $name = "rt-fakecpux";
        $product = Product::factory(['product_name' => $this->availabilityZone()->id . ': ' . $name])->create();
        $this->assertEquals($product->name, $this->rtPrefix);
    }

    public function testReturnsFullHSNameNoHyphens()
    {
        $name = "hs-testcpux";
        $product = Product::factory(['product_name' => $this->availabilityZone()->id . ': ' . $name])->create();
        $this->assertEquals($product->name, $name);

        $name = "hs-fail-cpu";
        $product = Product::factory(['product_name' => $this->availabilityZone()->id . ': ' . $name])->create();
        $this->assertEquals($product->name, 'hs-fail');
    }
}
