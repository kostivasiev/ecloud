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


        Schema::create('ucs_reseller', function (Blueprint $table) {
            $table->increments('ucs_reseller_id');
            $table->integer('ucs_reseller_reseller_id');
            $table->integer('ucs_reseller_datacentre_id');
            $table->string('ucs_reseller_solution_name');
            $table->string('ucs_reseller_active');
            $table->string('ucs_reseller_status');
        });


        Schema::create('ucs_node', function (Blueprint $table) {
            $table->increments('ucs_node_id');
            $table->integer('ucs_node_reseller_id');
            $table->integer('ucs_node_ucs_reseller_id');
            $table->integer('ucs_node_datacentre_id');
            $table->integer('ucs_node_specification_id');
            $table->string('ucs_node_status');
        });

        Schema::create('ucs_specification', function (Blueprint $table) {
            $table->increments('ucs_specification_id');
            $table->string('ucs_specification_active');
            $table->string('ucs_specification_friendly_name');
            $table->integer('ucs_specification_cpu_qty');
            $table->integer('ucs_specification_cpu_cores');
            $table->string('ucs_specification_cpu_speed');
            $table->string('ucs_specification_ram');
        });

        DB::table('ucs_specification')->insert(
            array(
                'ucs_specification_id' => 1,
                'ucs_specification_active' => 'Yes',
                'ucs_specification_friendly_name' => '2 x Oct Core 2.7Ghz (E5-2680 v1) 128GB',
                'ucs_specification_cpu_qty' => 2,
                'ucs_specification_cpu_cores' => 8,
                'ucs_specification_cpu_speed' => '2.7Ghz',
                'ucs_specification_ram' => '128GB',
            )
        );


        Schema::create('reseller_lun', function (Blueprint $table) {
            $table->increments('reseller_lun_id');
            $table->integer('reseller_lun_reseller_id');
            $table->integer('reseller_lun_ucs_reseller_id');
            $table->integer('reseller_lun_ucs_site_id');
            $table->string('reseller_lun_friendly_name');
            $table->string('reseller_lun_status');
            $table->string('reseller_lun_type');
            $table->integer('reseller_lun_size_gb');
            $table->string('reseller_lun_name');
            $table->string('reseller_lun_wwn');
            $table->string('reseller_lun_lun_type');
            $table->string('reseller_lun_lun_sub_type');
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


        Schema::create('metadata', function (Blueprint $table) {
            $table->increments('metadata_id');
            $table->string('metadata_key');
            $table->longText('metadata_value');
            $table->dateTime('metadata_created');
            $table->integer('metadata_reseller_id');
            $table->string('metadata_resource');
            $table->integer('metadata_resource_id');
            $table->string('metadata_createdby');
            $table->integer('metadata_createdby_id');
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
        Schema::dropIfExists('ucs_specification');
        Schema::dropIfExists('ucs_node');
        Schema::dropIfExists('reseller_lun');
        Schema::dropIfExists('servers');
        Schema::dropIfExists('server_subtype');
    }
}
