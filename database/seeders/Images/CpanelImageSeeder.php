<?php

namespace Database\Seeders\Images;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Image;
use App\Models\V2\ImageMetadata;
use App\Models\V2\ImageParameter;
use Illuminate\Database\Seeder;
use function app;
use function factory;

class CpanelImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Ubuntu 20.04 x86_64 - Plesk
         */
        $imageData = [
            'name' => 'cPanel & WHM - Pro Edition',
            'vpc_id' => null,
            'logo_uri' => 'https://images.ukfast.co.uk/logos/cpanel/300x300_white.png',
            'documentation_uri' => 'https://docs.cpanel.net/',
            'description' => 'cPanel & WebHost Manager (WHM) is a suite of tools built for Linux OS that gives you the ability to automate web hosting tasks via a simple graphical user interface. Its goal — to make managing servers easier for you and managing websites easier for your customers. 

The cPanel interface allows your customers to do a multitude of things to manage their sites, intranets, and keep their online properties running smoothly.

The WebHost Manager (WHM) interface has been tailor-made for hosting customers to get the most out of their servers so they can offer the most to their customers

This appliance comes with a cPanel Admin license*, created for a small to mid-level agencies and businesses, application developers, and web designers only needing a few accounts.  If you require a different license, please contact our team who will be happy to advise on the avaialble options.

* Additional license fees apply',
            'script_template' => <<<'EOM'
cat > /tmp/cpanelinstall <<\EOF
ARG_HOSTNAME="{{cpanel_hostname}}"
hostnamectl set-hostname $ARG_HOSTNAME

ARG_PRIMARYIP=$(ifconfig | grep inet | head -1 |sed 's/\:/ /g'|awk '{print $2}')
NETMASK=$(ifconfig | grep inet | head -1 |sed 's/\:/ /g' | awk '{print $4}')
GATEWAY=$(route -n | grep "^0.0.0.0" | awk '{print $2}')
sed -i '/BOOTPROTO=dhcp/BOOTPROTO=static/' /etc/sysconfig/network-scripts/ifcfg-eth0
echo "IPADDR=$ARG_PRIMARYIP" >> /etc/sysconfig/network-scripts/ifcfg-eth0
echo "NETMASK=$NETMASK" >> /etc/sysconfig/network-scripts/ifcfg-eth0
echo "GATEWAY=$GATEWAY" >> /etc/sysconfig/network-scripts/ifcfg-eth0
systemctl restart networking

$( mkdir /var/tmp/cpanelinstalltmp )
$( touch /var/tmp/cpanelinstall.lock )
$( echo "exclude=apache* bind-chroot courier* dovecot* exim* httpd* mod_ssl* mydns* mysql* nsd* php* proftpd* pure-ftpd* ruby* spamassassin* squirrelmail*" >> /etc/yum.conf )
$( echo "ADDR ${ARG_PRIMARYIP}" >  /etc/wwwacct.conf )
$( echo "CONTACTEMAIL root@${ARG_HOSTNAME} " >>  /etc/wwwacct.conf )
$( echo "CONTACTPAGER" >>  /etc/wwwacct.conf )
$( echo "DEFMOD x3" >>  /etc/wwwacct.conf )
$( echo "ETHDEV eth0" >>  /etc/wwwacct.conf )
$( echo "HOMEDIR /home" >>  /etc/wwwacct.conf )
$( echo "HOMEMATCH home" >>  /etc/wwwacct.conf )
$( echo "HOST ${ARG_HOSTNAME}" >>  /etc/wwwacct.conf )
$( echo "LOGSTYLE combined" >>  /etc/wwwacct.conf )
$( echo "MINUID" >>  /etc/wwwacct.conf )
$( echo "NS ns1.${ARG_HOSTNAME}" >>  /etc/wwwacct.conf )
$( echo "NS2 ns2.${ARG_HOSTNAME}" >>  /etc/wwwacct.conf )
$( echo "NS3" >>  /etc/wwwacct.conf )
$( echo "NS4" >>  /etc/wwwacct.conf )
$( echo "NSTTL 86400" >>  /etc/wwwacct.conf )
$( echo "SCRIPTALIAS y" >>  /etc/wwwacct.conf )
$( echo "TTL 14400" >>  /etc/wwwacct.conf )
$( echo "" >>  /etc/wwwacct.conf )
$( cd /home )
$( wget -N http://httpupdate.cpanel.net/latest -O /home/latest )
$( chmod +x /home/latest )
bash /home/latest --force &
ARG_PPID=$!
echo ${ARG_PPID} > /var/tmp/oops
wait "${ARG_PPID}"
$( touch /var/cpanel/version/securetmp_disabled )
$( rm /var/tmp/cpanelinstall.lock )
$( rm -f /etc/my.cnf )
$( echo "[mysqld]" >>  /etc/my.cnf )
$( echo "max_connections=500" >>  /etc/my.cnf )
$( echo "log-slow-queries" >>  /etc/my.cnf )
$( echo "# UKFast monitoring ranges, please do not remove." >> /etc/greylist_trusted_netblocks )
$( echo "81.201.136.192/27" >> /etc/greylist_trusted_netblocks )
$( echo "94.229.162.0/27" >> /etc/greylist_trusted_netblocks )
$( echo "81.201.136.168/29" >> /etc/greylist_trusted_netblocks )
$( echo "46.37.163.128/26" >> /etc/greylist_trusted_netblocks )
$( echo "# End UKFast monitoring ranges." >> /etc/greylist_trusted_netblocks )
$( chown root:mail /etc/greylist_trusted_netblocks )
$( chmod 0640 /etc/greylist_trusted_netblocks )
$( service exim restart )
if [ ! -x /tmp/.nocpanelreboot ]; then
    $( shutdown -r now )
fi
EOF

if ! chmod +x /tmp/cpanelinstall; then echo "Failed to chmod /tmp/cpanelinstall"; exit 7; fi
echo "/bin/bash /tmp/cpanelinstall"|at now > /dev/null
exit $?
EOM,
            'readiness_script' => <<<'EOM'
if [ -f /var/log/cpanel-install.log ]
then 
    if grep -q 'Thank you for installing cPanel' /var/log/cpanel-install.log
    then 
        exit 0
    fi

    if grep -q '(FATAL)' /var/log/cpanel-install.log
    then
        tail -n 25 /var/log/cpanel-install.log
        exit 1
    fi
fi

exit 2
EOM,
            'vm_template' => 'CentOS7 x86_64',
            'platform' => 'Linux',
            'active' => true,
            'public' => true,
            'visibility' => Image::VISIBILITY_PUBLIC,
        ];

        if (app()->environment() != 'production') {
            $imageData['id'] = 'img-cpanel';
        }

        $image = factory(Image::class)->create($imageData);

        // Sync the pivot table
        $image->availabilityZones()->sync(AvailabilityZone::all()->pluck('id')->toArray());

        factory(ImageMetadata::class)->create([
            'image_id' => $image->id,
            'key' => 'ukfast.license.identifier',
            'value' => 21163,
        ]);

        factory(ImageMetadata::class)->create([
            'image_id' => $image->id,
            'key' => 'ukfast.license.type',
            'value' => 'cpanel',
        ]);

        factory(ImageMetadata::class)->create([
            'image_id' => $image->id,
            'key' => 'ukfast.fip.required',
            'value' => 'true',
        ]);

        factory(ImageParameter::class)->create([
            'image_id' => $image->id,
            'name' => 'Hostname',
            'key' => 'cpanel_hostname',
            'type' => 'String',
            'description' => 'Fully qualified hostname',
            'required' => true,
            'validation_rule' => '/\w+/',
        ]);
    }
}
