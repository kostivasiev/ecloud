<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropBurstSizeColumnFromRouterThroughputsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('router_throughputs', function (Blueprint $table) {
            $table->integer('burst_size')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('router_throughputs', function (Blueprint $table) {
            $table->integer('burst_size')->change();
        });
    }
}
