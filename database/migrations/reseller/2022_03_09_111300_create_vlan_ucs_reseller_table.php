<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVlanUcsResellerTable extends Migration
{
    public function up()
    {
        Schema::connection('reseller')->create('vlan_ucs_reseller', function (Blueprint $table) {
            $table->increments('vlan_ucs_reseller_id');
            $table->integer('vlan_ucs_reseller_vlan_id');
            $table->integer('vlan_ucs_reseller_ucs_reseller_id');
        });
    }

    public function down()
    {
        Schema::connection('reseller')->dropIfExists('vlan_ucs_reseller');
    }
}
