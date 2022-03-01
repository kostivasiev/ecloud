<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouterThroughputsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('router_throughputs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('availability_zone_id');
            $table->string('name');
            $table->integer('committed_bandwidth');
            $table->integer('burst_size');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('router_throughputs');
    }
}
