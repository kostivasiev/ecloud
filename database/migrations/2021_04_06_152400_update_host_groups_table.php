<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class UpdateHostGroupsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('host_groups', function ($table) {
            $table->boolean('windows_enabled')->after('host_spec_id')->default(false);
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('host_groups', function ($table) {
            $table->dropColumn('windows_enabled');
        });
    }
}
