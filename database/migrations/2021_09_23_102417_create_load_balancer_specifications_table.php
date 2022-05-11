<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoadBalancerSpecificationsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('load_balancer_specifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('node_count');
            $table->integer('cpu');
            $table->integer('ram');
            $table->integer('hdd');
            $table->integer('iops');
            $table->string('image_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('load_balancer_specifications');
    }
}
