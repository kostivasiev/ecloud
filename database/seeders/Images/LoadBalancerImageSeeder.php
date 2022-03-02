<?php

namespace Database\Seeders\Images;

use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use Illuminate\Database\Seeder;
use function factory;

class LoadBalancerImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $image = Image::factory()->create([
            'id' => 'img-loadbalancer',
            'name' => 'Ubuntu 20.04 LBv2',
            'vpc_id' => null,
            'logo_uri' => null,
            'documentation_uri' => null,
            'description' => 'Load Balancer Image',
            'script_template' => <<<'EOM'
#!/bin/bash
set -euo pipefail

PRIMARYIP=$(ip --json route get 8.8.8.8 | /opt/warden/venv/bin/python3 -c 'import sys,json;print(json.load(sys.stdin)[0]["prefsrc"])')

cat > /etc/haproxy/conf.d/000_stats.cfg <<EOF
########################################################################
#                         UKFast Loadbalancer                          #
#                                                                      #
#  This file is managed by Warden. Please do not make changes to this  #
#    file as they may be overwritten during a future configuration     #
#     update. If you believe a manual change is required here, this    #
#             should be considered a bug, please report it.            #
#                                                                      #
########################################################################

listen stats 
    bind             ${PRIMARYIP}:8090
    mode             http
    stats            enable
    stats            hide-version
    stats auth       ukfast_admin:{{{stats_password}}}
    maxconn          10
    stats refresh    2s
    stats uri        /ukfast?l7stats
    server stats     127.0.0.1:8090
    http-request     use-service prometheus-exporter if { path /metrics }
EOF

cat >> /etc/hosts <<EOF
{{{nats_proxy_ip}}} ed-01.prod.ukfast.co.uk
EOF

cat > /etc/warden/nats.creds <<EOF
{{{nats_credentials}}}
EOF

cat > /etc/warden/config.json <<EOF
{
    "group_id": {{{group_id}}},
    "node_id": {{{node_id}}},
    "credentials": "/etc/warden/nats.creds",
    "nats_servers": ["tls://ed-01.prod.ukfast.co.uk:4222"]
}
EOF

export WARDEN_LBSTATE_PRIMARY="{{{primary}}}"
export WARDEN_LBSTATE_SRC_IP="${PRIMARYIP}"
export WARDEN_LBSTATE_PEERS="{{{peers}}}"
export WARDEN_LBSTATE_PASSWORD="{{{keepalived_password}}}"
export WARDEN_LBSTATE_VIPS4="{{{vips4}}}"
export WARDEN_LBSTATE_VIPS6="{{{vips6}}}"
export WARDEN_LBSTATE_PRIORITY_ADJ="{{{priority_adjustment}}}"

if [[ -z $WARDEN_LBSTATE_PEERS ]]; then
    unset WARDEN_LBSTATE_PEERS
fi

if [[ -z $WARDEN_LBSTATE_PRIORITY_ADJ ]]; then
    export WARDEN_LBSTATE_PRIORITY_ADJ="0"
fi

/opt/warden/venv/bin/warden --lb-init-from-env
systemctl enable --now keepalived
systemctl enable --now warden
exit 0
EOM,
            'vm_template' => 'ubuntu2004-lbv2-v1.0.0',
            'platform' => 'Linux',
            'active' => true,
            'public' => false,
            'visibility' => Image::VISIBILITY_PUBLIC,
        ]);

        // Sync the pivot table
        $image->availabilityZones()->sync('az-aaaaaaaa');
    }
}
