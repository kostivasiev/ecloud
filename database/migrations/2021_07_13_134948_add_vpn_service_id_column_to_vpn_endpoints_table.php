<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVpnServiceIdColumnToVpnEndpointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('vpn_endpoints', function (Blueprint $table) {
            $table->uuid('vpn_service_id')->after('name')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('vpn_endpoints', function (Blueprint $table) {
            $table->dropColumn(['vpn_service_id']);
        });
    }
}
