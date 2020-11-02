<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdditionalColumnsInFirewallRulesTable2 extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('firewall_rules', function (Blueprint $table) {
            $table->integer('sequence')->after('name')->nullable();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('firewall_rules', function (Blueprint $table) {
            $table->dropColumn(['sequence']);
        });
    }
}
