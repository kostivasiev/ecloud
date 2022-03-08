<?php

namespace App\Models\V2;

use App\Events\V2\VpnSession\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VpnSessionNetwork extends Model implements ResellerScopeable, Natable, RouterScopable
{
    use HasFactory, CustomKey, SoftDeletes, DeletionRules;

    public $keyPrefix = 'vpnsn';

    const TYPE_LOCAL = 'local';
    const TYPE_REMOTE = 'remote';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'vpn_session_id',
            'type',
            'ip_address',
        ];

        $this->dispatchesEvents = [
            'deleted' => Deleted::class,
        ];

        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->vpnSession->getResellerId();
    }

    public function getIPAddress(): string
    {
        return $this->ip_address;
    }

    public function getRouter()
    {
        return $this->vpnSession->vpnService->router;
    }

    public function vpnSession()
    {
        return $this->belongsTo(VpnSession::class);
    }

    public function localNoSNATs()
    {
        return $this->morphMany(Nat::class, 'sourceable', null, 'source_id');
    }

    public function remoteNoSNATs()
    {
        return $this->morphMany(Nat::class, 'destinationable', null, 'destination_id');
    }
}
