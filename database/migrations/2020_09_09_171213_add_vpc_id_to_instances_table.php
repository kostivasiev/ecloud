<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVpcIdToInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::connection('ecloud')->hasTable('instances')) {
            Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
                if (!Schema::connection('ecloud')->hasColumn('instances', 'vpc_id')) {
                    $table->uuid('vpc_id')->after('network_id')->nullable();
                }
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
        if (Schema::connection('ecloud')->hasTable('instances')) {
            Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
                if (!Schema::connection('ecloud')->hasColumn('instances', 'vpc_id')) {
                    $table->dropColumn('vpc_id');
                }
            });
        }
    }
}
