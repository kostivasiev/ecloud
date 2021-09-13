<?php

namespace App\Models\V2;

use App\Events\V2\VpnSession\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class VpnSessionNetwork extends Model implements ResellerScopeable, Natable
{
    use CustomKey, SoftDeletes, DeletionRules;

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

    public function vpnSession()
    {
        return $this->belongsTo(VpnSession::class);
    }
}
