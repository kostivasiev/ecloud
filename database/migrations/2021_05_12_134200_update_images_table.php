<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class UpdateImagesTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('images', function ($table) {
            $table->string('name');
            $table->string('logo_uri');
            $table->string('documentation_uri');
            $table->text('description');
            $table->boolean('active')->default(true);
            $table->boolean('is_public')->default(false);

            $table->string('template');


        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('host_groups', function ($table) {
            $table->dropColumn(['appliance_version_id']);
        });
    }
}
