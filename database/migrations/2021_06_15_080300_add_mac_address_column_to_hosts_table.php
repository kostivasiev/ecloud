<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddMacAddressColumnToHostsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('hosts', function (Blueprint $table) {
            $table->char('mac_address', 17)->nullable()->after('host_group_id');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('hosts', function (Blueprint $table) {
            $table->dropColumn('mac_address');
        });
    }
}
