<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSourceActionToNatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('nats', function (Blueprint $table) {
            $table->string('action')->nullable();
            $table->uuid('source_id')->nullable();
            $table->text('sourceable_type')->nullable();
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
            $table->dropColumn(['action', 'source_id', 'sourceable_type']);
        });
    }
}
