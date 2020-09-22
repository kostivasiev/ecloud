<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveVcpuTiersFromInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::connection('ecloud')->hasColumn('instances', 'vcpu_tier')) {
            Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
                $table->dropColumn('vcpu_tier');
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
        if (!Schema::connection('ecloud')->hasColumn('instances', 'vcpu_tier')) {
            Schema::table('instances', function (Blueprint $table) {
                $table->uuid('vcpu_tier')->after('appliance_id')->default('');
            });
        }
    }
}
