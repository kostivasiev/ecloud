<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class UpdateImagesTableAddVisibility extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('images', function ($table) {
            $table->string('visibility')->nullable();
        });

        Schema::connection('ecloud')->table('images', function ($table) {
            $table->dropColumn('publisher');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('images', function ($table) {
            $table->dropColumn('visibility');
        });

        Schema::connection('ecloud')->table('images', function ($table) {
            $table->string('publisher')->nullable();
        });
    }
}
