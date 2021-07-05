[![pipeline status](https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/badges/master/pipeline.svg)](https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/commits/master)
[![coverage report](https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/badges/master/coverage.svg)](https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/commits/master)


# eCloud API

Customer facing API for our private cloud infrastructure and resources management

v1 endpoints are for managing our legacy cloud, aka Public/Hybrid/Private solutions

v2 endpoints for managing our new VPC based resources


### Getting Started

These instructions will help you get a copy of the project up and running for development and testing purposes. 
See deployment for notes on how to deploy the project on a live system.

### Prerequisites

- PHP v7.4
- Composer


### Installing


If you’re using the vhost.sh script then you can quickly run the following command

***Note: this should be run as your user and not as root or via sudo, this is to keep file/folder permissions correct.***

```
./vhost.sh new apio-ecloud apio-ecloud.{your-dns}.rnd.ukfast
```

If you are setting up manually, create a new vhost and configure for the PHP version mentioned above.


Next, checkout the repository on your local machine and then upload the files to the vhost folder you just created.  
*Note: you could checkout direct on the server but this tends to cause more issues than it solves so is not recommended*  


### Running the tests

Tests are run on each push to gitlab, you can run manually by running the following form the vhost docroot

```
mv .env-testing .env
./vendor/bin/phpunit

```


### Deployment

Deployment is managed automatically by our CI pipelines


UAT:

all changes pushed to a remote branch with an active Merge Request will trigger a UAT build.
this environment contains all changes on teh branch but connected to our dev database to allow for easier UAT.

- https://uat-ecloud-{mrID}.rndcloud-k3s-1.rnd.ukfast/



Staging: 

all changes merged to master are auto-deployed to our rndcloud staging environment to allow for internal development

- https://kong.staging.rnd.ukfast/ecloud/


Production:

Deployment to production is made on master branch tag, tags must follow semver 

- https://api.ukfast.io/ecloud/ 


If for some reason a rollback is required on production, we can auto-roll back to any previous deployment, if the image is still avaialble this is a near instant rollback, otherwise this may take a momment while the image rebuilds

to roll back, please see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/environments/2131


### Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on the process for submitting pull requests.

### Versioning

We use [SemVer](http://semver.org/) for versioning. For the production ready versions available, see the tags page on this project
