<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRdnsHostnameToFloatingIps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->string('rdns_hostname')->after('ip_address')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->dropColumn('rdns_hostname');
        });
    }
}
