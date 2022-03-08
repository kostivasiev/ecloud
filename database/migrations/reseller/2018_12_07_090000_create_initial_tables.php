<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->integer('ucs_datacentre_reseller_id')->default('0');
            $table->string('ucs_datacentre_public_name');
            $table->string('ucs_datacentre_active');
            $table->string('ucs_datacentre_api_enabled');
            $table->string('ucs_datacentre_public_enabled')->default('No');
            $table->string('ucs_datacentre_oneclick_enabled')->default('No');
            $table->integer('ucs_datacentre_vcl_server_id')->default('0');
            $table->integer('ucs_datacentre_vce_server_id')->default('0');
            $table->string('ucs_datacentre_ucs_api_url');
            $table->string('ucs_datacentre_vmware_api_url');
        });

        Schema::create('ucs_datacentre_location', function (Blueprint $table) {
            $table->increments('ucs_datacentre_location_id');
            $table->integer('ucs_datacentre_location_datacentre_id')->default('0');
            $table->string('ucs_datacentre_location_name')->default('');
        });

        Schema::create('ucs_reseller', function (Blueprint $table) {
            $table->increments('ucs_reseller_id');
            $table->integer('ucs_reseller_reseller_id');
            $table->string('ucs_reseller_active');
            $table->string('ucs_reseller_solution_name');
            $table->string('ucs_reseller_status');
            $table->integer('ucs_reseller_datacentre_id');
            $table->string('ucs_reseller_encryption_enabled')->default('No');
            $table->string('ucs_reseller_encryption_default')->default('No');
            $table->string('ucs_reseller_encryption_billing_type')->default('PAYG');
            $table->dateTime('ucs_reseller_start_date')->default('0000-00-00 00:00:00');
        });


        Schema::create('ucs_node', function (Blueprint $table) {
            $table->increments('ucs_node_id');
            $table->integer('ucs_node_reseller_id');
            $table->integer('ucs_node_ucs_reseller_id');
            $table->integer('ucs_node_datacentre_id');
            $table->integer('ucs_node_specification_id');
            $table->integer('ucs_node_location_id');
            $table->string('ucs_node_status');
            $table->string('ucs_node_internal_name')->default('');
        });

        Schema::create('ucs_specification', function (Blueprint $table) {
            $table->increments('ucs_specification_id');
            $table->string('ucs_specification_active');
            $table->string('ucs_specification_name');
            $table->string('ucs_specification_friendly_name');
            $table->integer('ucs_specification_cpu_qty');
            $table->integer('ucs_specification_cpu_cores');
            $table->string('ucs_specification_cpu_speed');
            $table->string('ucs_specification_ram');
        });

//        DB::table('ucs_specification')->insert(
//            array(
//                'ucs_specification_id' => 1,
//                'ucs_specification_active' => 'Yes',
//                'ucs_specification_friendly_name' => '2 x Oct Core 2.7Ghz (E5-2680 v1) 128GB',
//                'ucs_specification_cpu_qty' => 2,
//                'ucs_specification_cpu_cores' => 8,
//                'ucs_specification_cpu_speed' => '2.7Ghz',
//                'ucs_specification_ram' => '128GB',
//            )
//        );


        Schema::create('reseller_lun', function (Blueprint $table) {
            $table->increments('reseller_lun_id');
            $table->integer('reseller_lun_reseller_id');
            $table->integer('reseller_lun_ucs_reseller_id');
            $table->integer('reseller_lun_ucs_site_id');
            $table->string('reseller_lun_friendly_name');
            $table->string('reseller_lun_status');
            $table->string('reseller_lun_type');
            $table->integer('reseller_lun_size_gb');
            $table->string('reseller_lun_name')->default('');
            $table->string('reseller_lun_wwn')->default('');
            $table->string('reseller_lun_lun_type');
            $table->string('reseller_lun_lun_sub_type')->default('');
        });


        Schema::create('servers', function (Blueprint $table) {
            $table->increments('servers_id');
            $table->integer('servers_reseller_id');
            $table->string('servers_type');
            $table->string('servers_use_ip_management')->default('No');
            $table->string('servers_friendly_name');
            $table->string('servers_hostname');
            $table->string('servers_netnios_name')->default('');
            $table->string('servers_cpu')->default('');
            $table->string('servers_memory')->default('');
            $table->string('servers_hdd')->default('');
            $table->string('servers_platform')->default('');
            $table->string('servers_license')->default('');
            $table->string('servers_backup')->default('None');
            $table->string('servers_advanced_support')->default('No');
            $table->string('servers_status')->default('');
            $table->string('servers_ecloud_type')->default('');
            $table->string('servers_subtype_id');
            $table->string('servers_active')->default('y');
            $table->string('servers_ip')->default('');
            $table->integer('servers_ecloud_ucs_reseller_id');
            $table->string('servers_firewall_role')->default('N/A');
        });

        Schema::create('server_license', function (Blueprint $table) {
            $table->increments('server_license_id');
            $table->string('server_license_name');
            $table->string('server_license_friendly_name');
        });

        DB::table('server_license')->insert(
            [
                'server_license_id' => 1,
                'server_license_name' => 'CentOS7 x86_64',
                'server_license_friendly_name' => 'CentOS 7 64-bit'
            ],
            [
                'server_license_id' => 2,
                'server_license_name' => 'CentOS6 x86_64',
                'server_license_friendly_name' => 'CentOS 6 64-bit',
            ]
        );


        Schema::create('server_ip_address', function (Blueprint $table) {
            $table->increments('server_ip_address_id');
            $table->string('server_ip_address_server_id');
            $table->string('server_ip_address_internal_ip');
            $table->string('server_ip_address_external_ip');
            $table->string('server_ip_address_active');
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
            $table->integer('metadata_reseller_id');
            $table->string('metadata_key');
            $table->longText('metadata_value');
            $table->string('metadata_resource');
            $table->integer('metadata_resource_id');
            $table->dateTime('metadata_created');
            $table->string('metadata_createdby');
            $table->integer('metadata_createdby_id');
        });

        Schema::create('triggers', function (Blueprint $table) {
            $table->increments('trigger_id');
            $table->integer('trigger_reseller_id');
            $table->string('trigger_description');
            $table->integer('trigger_reference_id');
            $table->string('trigger_reference_name');
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
        Schema::dropIfExists('ucs_reseller');
        Schema::dropIfExists('servers');
        Schema::dropIfExists('server_license');
        Schema::dropIfExists('server_ip_address');
        Schema::dropIfExists('server_subtype');
        Schema::dropIfExists('metadata');
        Schema::dropIfExists('triggers');
    }
}
