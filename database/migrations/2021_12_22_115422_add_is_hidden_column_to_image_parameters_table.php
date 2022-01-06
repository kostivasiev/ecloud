<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsHiddenColumnToImageParametersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('image_parameters', function (Blueprint $table) {
            $table->boolean('is_hidden')->after('required')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('image_parameters', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
}
