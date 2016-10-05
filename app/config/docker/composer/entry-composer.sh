#!/bin/bash

# Dockerfile defines this script as the ENTRYPOINT, so it is always executed as the container is first launched. We swap Gemfiles, execute entry-rspec.rb, run rspec, and then swap Gemfiles back.

# output included in docker logs output
echo 'ENTRY-SH: starting config/Docker/composer/entry-composer.sh'

echo 'ENTRY-SH: removing composer.lock and vender directory'
cd /mnt/aws
rm -f ./composer.lock
rm -fR ./vender

php /mnt/composer.phar require aws/aws-sdk-php
