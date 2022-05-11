<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeletedIndexToFloatingIpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->integer('deleted', false, true)->default(0);
            $table->unique(['ip_address', 'deleted'], 'idx_unique_ip_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Separate statement to satisfy SQLite dropping the index
        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->dropUnique('idx_unique_ip_address');
        });

        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->dropColumn(['deleted']);
        });
    }
}
