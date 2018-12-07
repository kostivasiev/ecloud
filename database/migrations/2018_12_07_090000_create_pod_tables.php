<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePodTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ucs_datacentre', function (Blueprint $table) {
            $table->increments('ucs_datacentre_id');
            $table->string('ucs_datacentre_public_name');
            $table->string('ucs_datacentre_active');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ucs_datacentre');
    }
}
