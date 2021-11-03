<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropApplianceVersionIdFromImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('images', function ($table) {
            $table->dropColumn(['appliance_version_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('images', function ($table) {
            $table->uuid('appliance_version_id')->nullable();
        });
    }
}
