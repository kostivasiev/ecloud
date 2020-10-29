<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveRouterIdFromFirewallRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('firewall_rules', function (Blueprint $table) {
            $table->dropColumn('router_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('firewall_rules', function (Blueprint $table) {
            $table->string('router_id')->nullable()->after('sequence');
        });
    }
}
