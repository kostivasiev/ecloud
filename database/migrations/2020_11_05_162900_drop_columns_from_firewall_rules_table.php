<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropColumnsFromFirewallRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('firewall_rules', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'source_ports', 'destination_ports']);
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
            $table->string('service_type')->nullable()->after('firewall_policy_id');
            $table->string('source_ports')->nullable()->after('source');
            $table->string('destination_ports')->nullable()->after('destination');
        });
    }
}
