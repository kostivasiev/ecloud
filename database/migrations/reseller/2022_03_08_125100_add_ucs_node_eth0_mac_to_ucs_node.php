<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUcsNodeEth0MacToUcsNode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('reseller')->table('ucs_node', function (Blueprint $table) {
            $table->string('ucs_node_eth0_mac')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('reseller')->table('ucs_node', function (Blueprint $table) {
            $table->dropColumn('ucs_node_eth0_mac');
        });
    }
}
