<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHostGroupIdToInstancesTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
            $table->uuid('host_group_id')->after('backup_enabled')->default('');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
            $table->dropColumn('host_group_id');
        });
    }
}
