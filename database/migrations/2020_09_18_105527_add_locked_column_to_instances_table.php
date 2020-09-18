<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLockedColumnToInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (
            Schema::connection('ecloud')->hasTable('instances') &&
            !Schema::connection('ecloud')->hasColumn('instances', 'locked')
        ) {
            Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
                $table->boolean('locked')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (
            Schema::connection('ecloud')->hasTable('instances') &&
            Schema::connection('ecloud')->hasColumn('instances', 'locked')
        ) {
            Schema::connection('ecloud')->table('instances', function (Blueprint $table) {
                $table->dropColumn('locked');
            });
        }
    }
}
