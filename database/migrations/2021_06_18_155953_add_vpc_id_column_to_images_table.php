<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVpcIdColumnToImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('images', function (Blueprint $table) {
            $table->uuid('vpc_id')->after('name')->nullable();
        });

        Schema::connection('ecloud')->table('images', function (Blueprint $table) {
            $table->dropColumn(['reseller_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('images', function (Blueprint $table) {
            $table->bigInteger('reseller_id')->nullable();
        });

        Schema::connection('ecloud')->table('images', function (Blueprint $table) {
            $table->dropColumn(['vpc_id']);
        });
    }
}
