<?php

namespace App\Models\V1;

use App\Services\Artisan\V1\ArtisanService;
use Illuminate\Database\Eloquent\Model;

class Storage extends Model
{
    protected $table = 'ucs_storage';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function password()
    {
        //todo: check we have a record returned
        return $this->hasMany(
            'App\Models\V1\ServerDetail',
            'server_detail_server_id',
            'server_id'
        )
            ->where('server_detail_type', '=', 'API')
            ->where('server_detail_user', '=', ArtisanService::ARTISAN_API_USER)->firstOrFail()->password();
    }

    public function port()
    {
        return $this->hasMany(
            'App\Models\V1\ServerDetail',
            'server_detail_server_id',
            'server_id'
        )
            ->where('server_detail_type', '=', 'API')
            ->where('server_detail_user', '=', ArtisanService::ARTISAN_API_USER)->firstOrFail()->server_detail_login_port;
    }


    public function datacentreId()
    {
        return $this->ucs_datacentre_id;
    }
}
