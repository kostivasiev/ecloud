<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VirtualPrivateCloudsAddRegionId extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('vpcs', function (Blueprint $table) {
            $table->uuid('region_id')->default('');
        });
    }

    public function down()
    {
        //No idea why this isnt working right now...
//        Schema::connection('ecloud')->table('vpcs', function(Blueprint $table) {
//            $table->dropColumn('region_id');
//        });
    }
}
