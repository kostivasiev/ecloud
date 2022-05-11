<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNetworkIdToIpAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('ip_addresses', function (Blueprint $table) {
            $table->uuid('network_id')->nullable()->after('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('ip_addresses', function (Blueprint $table) {
            $table->dropColumn(['network_id']);
        });
    }
}
