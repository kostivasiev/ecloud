<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUniqueIndexFromIpAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('ip_addresses', function (Blueprint $table) {
            $table->dropUnique('ip_addresses_ip_address_deleted_at_unique');
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
            $table->unique(['ip_address', 'deleted_at'], 'ip_addresses_ip_address_deleted_at_unique');
        });
    }
}
