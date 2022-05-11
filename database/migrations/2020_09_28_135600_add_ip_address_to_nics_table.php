<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIpAddressToNicsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('nics', function (Blueprint $table) {
            $table->ipAddress('ip_address')->after('network_id')->nullable();
            $table->boolean('deleted')->default(false);
            $table->unique(['ip_address', 'network_id', 'deleted'], 'idx_unique_ip');
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
        Schema::connection('ecloud')->table('nics', function (Blueprint $table) {
            $table->dropUnique('idx_unique_ip');
        });

        Schema::connection('ecloud')->table('nics', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'deleted']);
        });
    }
}
