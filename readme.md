# eCloud API

[![build status](https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/badges/master/build.svg)](https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/commits/master)

The eCloud API

## Getting Started

### Prerequisites

- Composer
- Gavin's vhost script (Unless you want to manually set up your apache.conf file... )

### Installing

- `sudo ./vhost.sh new ecloud.api.ukfast.io ecloud-apio.{your-name}.rnd.ukfast`
- `cd /home/vhost/ecloud-apio.{your-name}.rnd.ukfast`
- `git clone git@gitlab.devops.ukfast.co.uk:ukfast/api.ukfast/ecloud.git`
- Using your favourite text editor (vim, of course), edit the apache.conf file so that your DocumentRoot is `/public` instead of `/html`
- The API should now be live at `https://ecloud-apio.{your-name}.rnd.ukfast`
- Happy Days!


## Built With

* [Lumen](https://lumen.laravel.com/) - The web framework used
* [Composer](https://getcomposer.org/) - Dependency Management
