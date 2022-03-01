<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPriceTable extends Migration
{
    public function up()
    {
        Schema::connection('reseller')->create('product_price', function (Blueprint $table) {
            $table->increments('product_price_id');
            $table->integer('product_price_product_id');
            $table->enum('product_price_type', ['Standard', 'Server', 'Partner'])->default('Standard');
            $table->float('product_price_sale_price');
        });
    }

    public function down()
    {
        Schema::connection('reseller')->dropIfExists('product_price');
    }
}
