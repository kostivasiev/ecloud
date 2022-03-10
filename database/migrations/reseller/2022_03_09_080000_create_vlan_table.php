<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVlanTable extends Migration
{
    public function up()
    {
        Schema::connection('reseller')->create('vlan', function (Blueprint $table) {
            $table->increments('vlan_id');
            $table->integer('vlan_number');
            $table->string('vlan_public_name');
            $table->string('vlan_interface_name');
            $table->text('vlan_description');
            $table->integer('vlan_reseller_id');
            $table->integer('vlan_vlan_group_id');
            $table->enum('vlan_is_otv',  ['Yes', 'No'])->default('No');
            $table->integer('vlan_vlan_type_id')->default(1);
            $table->enum('vlan_allow_client_vm_launch', ['Yes', 'No'])->default('No');
        });
    }

    public function down()
    {
        Schema::connection('reseller')->dropIfExists('vlan');
    }
}
