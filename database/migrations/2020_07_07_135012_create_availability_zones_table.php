<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilityZonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('availability_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->integer('datacentre_site_id');
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('availability_zones');
    }
}
