<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

Class VirtualPrivateCloudsAddRegionId extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('virtual_private_clouds', function($table) {
            $table->uuid('region_id')->default('');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('virtual_private_clouds', function($table) {
            $table->dropColumn('region_id');
        });
    }
}
