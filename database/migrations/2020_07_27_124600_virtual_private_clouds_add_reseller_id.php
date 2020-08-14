<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class VirtualPrivateCloudsAddResellerId extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('virtual_private_clouds', function($table) {
            $table->bigInteger('reseller_id')->default('');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('virtual_private_clouds', function($table) {
            $table->dropColumn('reseller_id');
        });
    }
}
