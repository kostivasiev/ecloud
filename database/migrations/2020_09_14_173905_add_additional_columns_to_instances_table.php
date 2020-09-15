<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdditionalColumnsToInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
            $table->uuid('appliance_id')->after('vpc_id')->default('');
            $table->uuid('vcpu_tier')->after('appliance_id')->default('');
            $table->integer('vcpu_cores')->after('vcpu_tier')->default(1);
            $table->integer('ram_capacity')->after('vcpu_count')->default(1024);
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
            $table->dropColumn(['appliance_id', 'vcpu_tier', 'vcpu_cores', 'ram_capacity']);
        });
    }
}
