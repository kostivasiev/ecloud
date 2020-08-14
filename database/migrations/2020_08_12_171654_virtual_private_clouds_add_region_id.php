<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Class VirtualPrivateCloudsAddRegionId extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('virtual_private_clouds', function(Blueprint $table) {
            $table->string('region_id')->default('');
        });
    }

    public function down()
    {
         //No idea why this isnt working right now...
//        Schema::connection('ecloud')->table('virtual_private_clouds', function(Blueprint $table) {
//            $table->dropColumn('region_id');
//        });
    }
}
