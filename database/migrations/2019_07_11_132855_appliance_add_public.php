<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class ApplianceAddPublic extends Migration
{
    /**
     * Adds appliance_is_public column to appliance table
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('appliance', function ($table) {
            $table->enum('appliance_is_public', ['Yes', 'No'])->default('Yes');
        });
    }
}
