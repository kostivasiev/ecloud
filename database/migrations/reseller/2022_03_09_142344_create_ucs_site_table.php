<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ucs_site', function (Blueprint $table) {
            $table->increments('ucs_site_id');
            $table->integer('ucs_site_ucs_reseller_id');
            $table->integer('ucs_site_ucs_datacentre_id');
            $table->enum('ucs_site_state', ['Active', 'Passive']);
            $table->integer('ucs_site_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ucs_site');
    }
};
