<?php

namespace App\Models\V1;

use App\Encryption\AesEncryption;
use App\Encryption\RemoteKeyStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Resource\Property\BooleanProperty;
use UKFast\Api\Resource\Property\EncryptionProperty;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;

/**
 * Creates Illuminate\Database\Eloquent\Model
 * @category   API_Server_Credentials
 * @package    App\Models\V1
 * @subpackage ServerDetail
 * @author     Moien Ilyas <moien.ilyas@ukfast.co.uk>
 * @author     Paul McNally <paul.mcnally@ukfast.co.uk>
 * @license    Copyright 2018 UKFast.Net Ltd
 * @link       https://gitlab.devops.ukfast.co.uk/rnd/api.ukfast/server-details/
 */
class ServerDetail extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'server_detail';

    /**
     * The primary key associated with the model.
     * @var string
     */
    protected $primaryKey = 'server_detail_id';

    /**
     * Indicates if the model should be timestamped.
     * @var bool
     */
    public $timestamps = false;

    /**
     * Filters by server_detail_server_id
     * @param \Illuminate\Database\Query\Builder $query
     * @param int $serverId
     * @return \Illuminate\Database\Query\Builder
     * @throws \InvalidArgumentException
     */
    public function scopeWithParent($query, $serverId)
    {
        if (filter_var($serverId, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException('Invalid Server Id');
        }

        return $query->where('server_detail.server_detail_server_id', $serverId);
    }

    /**
     * Ditto - Implementation: mapping of database fields to friendly names
     * @return array
     */
    public function databaseNames()
    {
        return [
            'id' => 'server_detail.server_detail_id',
            'server_id' => 'server_detail.server_detail_server_id',
            'type' => 'server_detail.server_detail_type',
            'user' => 'server_detail.server_detail_user',
            'password' => 'server_detail.server_detail_random',
            'show_record' => 'server_detail.server_detail_show',
            'port' => 'server_detail.server_detail_login_port',
            'version' => 'server_detail.server_detail_ver',
            'active_directory_id' => 'server_detail.server_detail_ad_domain_id'
        ];
    }

    /**
     * Resource Package - Implementation: list of property types and transformation
     * @return array
     */
    public function properties()
    {
        return [
            IdProperty::create('server_detail_id', 'id'),
            IdProperty::create('server_detail_server_id', 'server_id'),
            StringProperty::create('server_detail_type', 'type'),
            StringProperty::create('server_detail_user', 'user'),
            EncryptionProperty::create(
                'server_detail_random',
                'password',
                null,
                'int/banner.html',
                null,
                null,
                null,
                null,
                env('AES_VECTOR')
            ),
            BooleanProperty::create('server_detail_show', 'show_record', null, 'y', 'n'),
            IntProperty::create('server_detail_login_port', 'port'),
            IntProperty::create('server_detail_ver', 'version', 2),
            IntProperty::create('server_detail_ad_domain_id', 'active_directory_id', 0),
        ];
    }


    /**
     * Load the server details password
     * If we have a plain text string in server_detail_pass column return that, otherwise attempt to decode from
     * server_detail_random
     * @return bool|string
     */
    public function getServerDetailPassAttribute()
    {
        if (!empty($this->attributes['server_detail_pass'])) {
            return $this->attributes['server_detail_pass'];
        }

        try {
            if (!empty($this->server_detail_random)) {
                $encryptionKey = RemoteKeyStore::getEncryptionKey();
                $encryption = new AESEncryption($encryptionKey, env('AES_VECTOR'));
                $this->attributes['server_detail_pass'] = $encryption->decrypt($this->server_detail_random);
            }

            if (empty($this->attributes['server_detail_pass'])) {
                throw new \Exception('no password set');
            }
        } catch (\Exception $exception) {
            Log::error('Failed to load password: ' . $exception->getMessage());
            return false;
        }

        return $this->attributes['server_detail_pass'];
    }

    /**
     * Returns the decrypted server detail password
     * @return mixed
     */
    public function getPassword()
    {
        return $this->server_detail_pass;
    }
}
