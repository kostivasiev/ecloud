<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResellerLunUcsStorageIdToResellerLun extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('reseller')->table('reseller_lun', function (Blueprint $table) {
            $table->integer('reseller_lun_ucs_storage_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('reseller')->table('reseller_lun', function (Blueprint $table) {
            $table->dropColumn('reseller_lun_ucs_storage_id');
        });
    }
}
