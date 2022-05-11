<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUcsResellerNplusOneToUcsReseller extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('reseller')->table('ucs_reseller', function (Blueprint $table) {
            $table->enum('ucs_reseller_nplusone_active', ['Yes', 'No'])->default('Yes');
            $table->enum('ucs_reseller_nplus_redundancy', ['None', 'N+1', 'N+N'])->default('None');
            $table->enum('ucs_reseller_nplus_overprovision', ['Yes', 'No'])->default('No');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('reseller')->table('ucs_reseller', function (Blueprint $table) {
            $table->dropColumn([
                'ucs_reseller_nplusone_active',
                'ucs_reseller_nplus_redundancy',
                'ucs_reseller_nplus_overprovision',
            ]);
        });
    }
}
