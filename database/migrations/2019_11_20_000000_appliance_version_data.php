<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ApplianceVersionData extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('appliance_version_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key', 127);
            $table->text('value');
            $table->string('appliance_version_uuid', 36);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->drop('appliance_version_data');
    }
}
