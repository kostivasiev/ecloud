<?php

use App\Models\V2\IpAddress;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        IpAddress::where('type', '=', 'normal')->each(function ($ipAddress) {
            $ipAddress->setAttribute('type', 'dhcp')->saveQuietly();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        IpAddress::where('type', '=', 'dhcp')->each(function ($ipAddress) {
            $ipAddress->setAttribute('type', 'normal')->saveQuietly();
        });
    }
};
