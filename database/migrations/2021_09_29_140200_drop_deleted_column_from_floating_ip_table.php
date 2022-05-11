<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropDeletedColumnFromFloatingIpTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->dropUnique('idx_unique_ip_address');
        });

        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->dropColumn(['deleted']);
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
            $table->integer('deleted', false, true)->default(0);
            $table->unique(['ip_address', 'deleted'], 'idx_unique_ip_address');
        });
    }
}
