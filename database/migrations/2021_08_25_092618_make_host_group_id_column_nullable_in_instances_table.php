<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeHostGroupIdColumnNullableInInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
            $table->uuid('host_group_id')->after('backup_enabled')->default('')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
            $table->uuid('host_group_id')->after('backup_enabled')->default('')->nullable(false)->change();
        });
    }
}
