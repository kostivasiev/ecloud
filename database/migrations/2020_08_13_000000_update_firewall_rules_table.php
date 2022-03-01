<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class UpdateFirewallRulesTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('firewall_rules', function ($table) {
            $table->uuid('router_id')->default('');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('firewall_rules', function ($table) {
            $table->dropColumn('router_id');
        });
    }
}
