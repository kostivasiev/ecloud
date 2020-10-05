<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class UpdateFirewallRulesTable2 extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('firewall_rules', function ($table) {
            $table->boolean('deployed')->default(false);
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('firewall_rules', function ($table) {
            $table->dropColumn('deployed');
        });
    }
}
