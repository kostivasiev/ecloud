<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameVpcTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->rename('virtual_private_clouds', 'vpcs');
    }

    public function down()
    {
        Schema::connection('ecloud')->rename('vpcs', 'virtual_private_clouds');
    }
}
