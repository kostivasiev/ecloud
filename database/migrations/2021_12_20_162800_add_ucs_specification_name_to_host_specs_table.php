<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUcsSpecificationNameToHostSpecsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('host_specs', function (Blueprint $table) {
            $table->renameColumn('name', 'ucs_specification_name');
        });
        Schema::connection('ecloud')->table('host_specs', function (Blueprint $table) {
            $table->string('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('host_specs', function (Blueprint $table) {
            $table->dropColumn('name');
        });
        Schema::connection('ecloud')->table('host_specs', function (Blueprint $table) {
            $table->renameColumn('ucs_specification_name', 'name');
        });
    }
}
