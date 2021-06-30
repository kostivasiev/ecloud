<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameVpnsTableToVpnServicesAndAddNameColumnToVpnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('vpns', function (Blueprint $table) {
            $table->rename('vpn_services');
            $table->string('name')->nullable()->after('router_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('vpn_services', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->rename('vpns');
        });
    }
}
