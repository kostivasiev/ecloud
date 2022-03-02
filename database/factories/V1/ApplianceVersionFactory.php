<?php
namespace Database\Factories\V1;

use App\Models\V1\ApplianceVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ApplianceVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ApplianceVersion::class;

    protected string $script_template = <<<'EOM'
mysql -e "UPDATE mysql.user SET password=PASSWORD('{{{ mysql_root_password }}}') WHERE User='root';"
mysql -e "CREATE DATABASE wordpress;"
mysql -e "CREATE USER wordpressuser@localhost IDENTIFIED BY '{{{ mysql_wordpress_user_password }}}';"
mysql -e "GRANT ALL PRIVILEGES ON wordpress.* TO wordpressuser@localhost IDENTIFIED BY '{{{ mysql_wordpress_user_password }}}';"
mysql -e "FLUSH PRIVILEGES;"

cat <<EOF > /root/.my.cnf
[client] 
user=root
password={{{ mysql_root_password }}}
EOF

sed -i "/'DB_NAME'/c\define('DB_NAME', 'wordpress');" /var/www/html/wp-config.php
sed -i "/'DB_USER'/c\define('DB_USER', 'wordpressuser');" /var/www/html/wp-config.php
sed -i "/'DB_PASSWORD'/c\define('DB_PASSWORD', '{{{ mysql_wordpress_user_password }}}');" /var/www/html/wp-config.php

{{#wordpress_url}}
UPDATE wp_options SET option_value = '{{{ wordpress_url }}}' WHERE option_name = 'siteurl';
UPDATE wp_options SET option_value = '{{{ wordpress_url }}}' WHERE option_name = 'home';
{{/ wordpress_url}}

AUTH_KEY=$(pwgen -syn 64 1)
SECURE_AUTH_KEY=$(pwgen -syn 64 1)
LOGGED_IN_KEY=$(pwgen -syn 64 1)
NONCE_KEY=$(pwgen -syn 64 1)
AUTH_SALT=$(pwgen -syn 64 1)
SECURE_AUTH_SALT=$(pwgen -syn 64 1)
LOGGED_IN_SALT=$(pwgen -syn 64 1)
NONCE_SALT=$(pwgen -syn 64 1)
sed -i "/'AUTH_KEY'/c\define('AUTH_KEY',         '$AUTH_KEY');" /var/www/html/wp-config.php
sed -i "/'SECURE_AUTH_KEY'/c\define('SECURE_AUTH_KEY',  '$SECURE_AUTH_KEY');" /var/www/html/wp-config.php
sed -i "/'LOGGED_IN_KEY'/c\define('LOGGED_IN_KEY',    '$LOGGED_IN_KEY');" /var/www/html/wp-config.php
sed -i "/'NONCE_KEY'/c\define('NONCE_KEY',        '$NONCE_KEY');" /var/www/html/wp-config.php
sed -i "/'AUTH_SALT'/c\define('AUTH_SALT',        '$AUTH_SALT');" /var/www/html/wp-config.php
sed -i "/'SECURE_AUTH_SALT'/c\define('SECURE_AUTH_SALT', '$SECURE_AUTH_SALT');" /var/www/html/wp-config.php
sed -i "/'LOGGED_IN_SALT'/c\define('LOGGED_IN_SALT',   '$LOGGED_IN_SALT');" /var/www/html/wp-config.php
sed -i "/'NONCE_SALT'/c\define('NONCE_SALT',       '$NONCE_SALT');" /var/www/html/wp-config.php
EOM;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'appliance_version_uuid' => Str::uuid(),
            //'appliance_version_appliance_id' => '',
            //'appliance_version_version' => '',
            'appliance_version_script_template' => $this->script_template,
            'appliance_version_vm_template' => 'centos7-wordpress-v1.0.0',
            'appliance_version_server_license_id' => 258,
            'appliance_version_active' => 'Yes'
        ];
    }
}
