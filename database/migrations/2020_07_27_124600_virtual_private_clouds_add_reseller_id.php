<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class VirtualPrivateCloudsAddResellerId extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('vpcs', function ($table) {
            $table->bigInteger('reseller_id')->default(0);
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('vpcs', function ($table) {
            $table->dropColumn('reseller_id');
        });
    }
}
