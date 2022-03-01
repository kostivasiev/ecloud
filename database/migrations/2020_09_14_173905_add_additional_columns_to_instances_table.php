<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->uuid('appliance_version_id')->after('vpc_id')->default('');
            $table->integer('vcpu_cores')->after('appliance_version_id')->nullable();
            $table->integer('ram_capacity')->after('vcpu_cores')->nullable();
            $table->boolean('locked')->default(false);
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
            $table->dropColumn(['appliance_version_id', 'vcpu_cores', 'ram_capacity', 'locked']);
        });
    }
}
