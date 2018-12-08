<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInitialTables extends Migration
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


        Schema::create('servers', function (Blueprint $table) {
            $table->increments('servers_id');
            $table->integer('servers_reseller_id');
            $table->string('servers_type');
            $table->string('servers_subtype_id');
            $table->string('servers_active');
            $table->string('servers_ip');
            $table->string('servers_hostname');
            $table->string('servers_friendly_name');
            $table->integer('servers_ecloud_ucs_reseller_id');
            $table->string('servers_firewall_role');
        });


        Schema::create('server_subtype', function (Blueprint $table) {
            $table->increments('server_subtype_id');
            $table->string('server_subtype_parent_type');
            $table->string('server_subtype_name');
        });

        DB::table('server_subtype')->insert(
            array(
                'server_subtype_id' => 1,
                'server_subtype_parent_type' => 'ecloud vm',
                'server_subtype_name' => 'VMware',
            )
        );

        DB::table('server_subtype')->insert(
            array(
                'server_subtype_id' => 2,
                'server_subtype_parent_type' => 'virtual firewall',
                'server_subtype_name' => 'eCloud Dedicated',
            )
        );
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ucs_datacentre');
        Schema::dropIfExists('servers');
        Schema::dropIfExists('server_subtype');
    }
}
