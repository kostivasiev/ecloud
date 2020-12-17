<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPriceCustomTable extends Migration
{
    public function up()
    {
        Schema::connection('reseller')->create('product_price_custom', function (Blueprint $table) {
            $table->increments('product_price_custom_id');
            $table->integer('product_price_custom_product_id');
            $table->integer('product_price_custom_reseller_id');
            $table->float('product_price_custom_sale_price');
        });
    }

    public function down()
    {
        Schema::connection('reseller')->dropIfExists('product_price_custom');
    }
}
