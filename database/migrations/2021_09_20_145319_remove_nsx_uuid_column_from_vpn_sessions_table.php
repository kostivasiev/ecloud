<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveNsxUuidColumnFromVpnSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('vpn_sessions', function (Blueprint $table) {
            $table->dropColumn(['nsx_uuid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('vpn_sessions', function (Blueprint $table) {
            $table->uuid('nsx_uuid')->after('name')->nullable()->index();
        });
    }
}
