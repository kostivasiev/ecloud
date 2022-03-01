<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterNatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('nats', function (Blueprint $table) {
            $table->renameColumn('destination', 'destination_id');
        });
        Schema::connection('ecloud')->table('nats', function (Blueprint $table) {
            $table->renameColumn('translated', 'translated_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('nats', function (Blueprint $table) {
            $table->renameColumn('destination_id', 'destination');
        });
        Schema::connection('ecloud')->table('nats', function (Blueprint $table) {
            $table->renameColumn('translated_id', 'translated');
        });
    }
}
