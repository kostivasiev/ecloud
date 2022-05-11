<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAvailabilityZoneIdToInstancesTable extends Migration
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
                if (!Schema::connection('ecloud')->hasColumn('instances', 'availability_zone_id')) {
                    $table->uuid('availability_zone_id')->after('vpc_id')->nullable();
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
                if (Schema::connection('ecloud')->hasColumn('instances', 'availability_zone_id')) {
                    $table->dropColumn('availability_zone_id');
                }
            });
        }
    }
}
