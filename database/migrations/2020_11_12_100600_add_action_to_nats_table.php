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
            $table->string('action');
            $table->uuid('source_id')->nullable();
            $table->text('sourceable_type')->nullable();

            $table->uuid('destination_id')->nullable(true)->change();
            $table->text('destinationable_type')->nullable(true)->change();
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

            $table->uuid('destination_id')->nullable(false)->change();
            $table->text('destinationable_type')->nullable(false)->change();
        });
    }
}
