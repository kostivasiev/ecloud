<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToLoadBalancerSpecsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('load_balancer_specifications', function (Blueprint $table) {
            $table->text('description')->nullable()->after('availability_zone_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('load_balancer_specifications', function (Blueprint $table) {
            $table->dropColumn(['description']);
        });
    }
}
