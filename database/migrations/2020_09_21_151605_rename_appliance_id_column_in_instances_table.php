<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameApplianceIdColumnInInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::connection('ecloud')->hasColumn('instances', 'appliance_id')) {
            Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
                $table->renameColumn('appliance_id', 'appliance_version_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::connection('ecloud')->hasColumn('instances', 'appliance_version_id')) {
            Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
                $table->renameColumn('appliance_version_id', 'appliance_id');
            });
        }
    }
}
