<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeDirectionColumnsToNetworkRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('network_rules', function (Blueprint $table) {
            $table->string('type')->nullable()->after('enabled');
            $table->string('direction')->nullable()->after('action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('network_rules', function (Blueprint $table) {
            $table->dropColumn(['type', 'direction']);
        });
    }
}
