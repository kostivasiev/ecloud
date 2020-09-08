[![build status](https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/badges/master/build.svg)](https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/commits/master)

# eCloud API

eCloud API for managing Public/Hybrid/Private/Burst


### Getting Started

These instructions will help you get a copy of the project up and running for development and testing purposes. 
See deployment for notes on how to deploy the project on a live system.

### Prerequisites

- PHP v7.1
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

Production: auto deployment via Gitlab + gitoverhere, triggered on Tag from master
- https://api.ukfast.io/ecloud/ (public facing)


### Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on the process for submitting pull requests.

### Versioning

We use [SemVer](http://semver.org/) for versioning. For the production ready versions available, see the tags page on this project
