<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductTable extends Migration
{
    public function up()
    {
        Schema::connection('reseller')->create('product', function (Blueprint $table) {
            $table->increments('product_id');
            $table->integer('product_sales_product_id')->nullable();
            $table->string('product_name');
            $table->enum('product_category', ['Domain', 'SSL', 'Shared Exchange', 'safeDNS', 'KVM', 'Webcel', 'vCloud', 'PSS', 'eCloud', 'OpenStack', 'Server', 'Redemption', 'Security Scan', 'Credit', 'FastCloudBackup', 'ddos_protection', 'Threat Monitoring', 'Threat Response', 'Phishing']);
            $table->string('product_subcategory');
            $table->string('product_supplier');
            $table->enum('product_active', ['Yes', 'No'])->default('Yes');
            $table->enum('product_duration_type', ['Year', 'Quarter', 'Month', 'Hour', 'Single'])->default('Single');
            $table->integer('product_duration_length')->default(1);
            $table->string('product_cost_currency')->nullable();
            $table->float('product_cost_price')->nullable();
        });
    }

    public function down()
    {
        Schema::connection('reseller')->dropIfExists('product');
    }
}
