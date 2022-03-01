<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFirewallRulePortsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('firewall_rule_ports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('firewall_rule_id');
            $table->string('protocol');
            $table->string('source')->nullable();
            $table->string('destination')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('firewall_rule_ports');
    }
}
