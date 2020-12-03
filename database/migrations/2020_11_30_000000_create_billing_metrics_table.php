<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingMetricsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('billing_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('resource_id');
            $table->uuid('vpc_id');
            $table->integer('reseller_id');
            $table->string('key');
            $table->string('value');
            $table->timestamp('start')->nullable();
            $table->timestamp('end')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('billing_metrics');
    }
}
