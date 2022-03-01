<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveNetworkIdFromInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::connection('ecloud')->hasTable('instances') &&
            Schema::connection('ecloud')->hasColumn('instances', 'network_id')
        ) {
            Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
                $table->dropColumn('network_id');
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
        if (Schema::connection('ecloud')->hasTable('instances') &&
            !Schema::connection('ecloud')->hasColumn('instances', 'network_id')
        ) {
            Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
                $table->uuid('network_id')->nullable()->after('id');
            });
        }
    }
}
