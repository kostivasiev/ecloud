<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddResellerIdColumnToTasksTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('tasks', function (Blueprint $table) {
            $table->bigInteger('reseller_id')->after('failure_reason')->default(0)->index();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('tasks', function (Blueprint $table) {
            $table->dropColumn('reseller_id');
        });
    }
}
