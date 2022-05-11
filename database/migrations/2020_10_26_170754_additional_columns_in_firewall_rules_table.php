<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdditionalColumnsInFirewallRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('firewall_rules', function (Blueprint $table) {
            $table->text('source')->after('firewall_policy_id')->nullable();
            $table->text('destination')->after('source')->nullable();
            $table->string('action')->after('destination')->nullable();
            $table->string('direction')->after('action')->nullable();
            $table->boolean('enabled')->after('direction')->default(true);
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
            $table->dropColumn(['source', 'destination', 'action', 'direction', 'enabled']);
        });
    }
}
