README
======

Todo?
-----

- Create services of the classes used to debianize.
- Create tests
- ...

Installation
------------

## Requirements

Make sure you have the following commands available: ar, tar

## Get the bundle

To install the bundle, place it in the `vendor/bundles/TON/Bundle` directory of your project
(so that it lives at `vendor/bundles/TON/Bundle/DebianizeBundle`). You can do this by adding
the bundle as a submodule, cloning it, or simply downloading the source.

    git submodule add https://github.com/21Net/DebianizeBundle.git vendor/bundles/TON/Bundle/DebianizeBundle


## Add the TON namespace to your autoloader

If this is the first TON bundle in your Symfony 2 project, you'll
need to add the `TON` namespace to your autoloader. This file is usually located at `app/autoload.php`.

    $loader->registerNamespaces(array(
        'TON'                       => __DIR__.'/../vendor/bundles'
        // ...
    ));

## Initializing the bundle

To initialize the bundle, you'll need to add it in your kernel. This
file is usually located at `app/AppKernel.php`. Loading it only in your dev environment is recommended.

    public function registerBundles()
    {
        // ...

        $bundles = array(
            ...,
            new TON\Bundle\DebianizeBundle\TONDebianizeBundle(),
        }
    )

## Configuration example

An example configuration:

    ton_debianize:
        install_location: /var/www/mysite
        additional_resources:
            vhost:
                source:  config/example.com
                destination: etc/apache2/sites-available/example.com
        additional_control_files:
            postinst:
                source: config/postinst
                destination: postinst
        package:
            name: mysite
            description: My cool site
            maintainer: Jonas Wouters <jonas@21net.com>

        deploy:
            username: root
            password: password
            host: example.com
            commands:
                move: "sudo mv ~/{file_name} /var/www/packages/lucid/pool/main/{file_name}"
                rebuild: "cd /var/www/packages/lucid/ || sudo apt-ftparchive generate apt-ftparchive.conf"
                list: "apt-ftparchive -c /var/www/packages/lucid/apt-release.conf release /var/www/packages/lucid/dists/lucid | sudo tee /var/www/packages/lucid/dists/lucid/Release"


postinst example (Located at app/comfig/postinst)

    #!/bin/sh

    # Warmup cache
    /var/www/mysite/app/console cache:warmup
