<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImageColumnToInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
            $table->uuid('image_id')->default('')->after('appliance_version_id');
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
            $table->dropColumn('image_id');
        });
    }
}
