<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeSourceDestinationColumnsNullableInNetworkRulePortsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('network_rule_ports', function (Blueprint $table) {
            $table->string('source')->nullable()->change();
            $table->string('destination')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('network_rule_ports', function (Blueprint $table) {
            $table->string('source')->change();
            $table->string('destination')->change();
        });
    }
}
