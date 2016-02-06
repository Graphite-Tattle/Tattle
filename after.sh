#!/bin/bash

# This file runs after the vagrant machine is provisioned via `vagrant up`
echo "Executing homestead extra provisioning ##################################"


echo "Installing ubuntu extras ################################################"
sudo apt-get install -y ack-grep;


echo "Run composer install and do database prep ###############################"
cd tattle
composer install

echo "<?
// DATABASE SETTINGS
\$GLOBALS['DATABASE_HOST'] = '127.0.0.1';
\$GLOBALS['DATABASE_PORT'] = '3306';
\$GLOBALS['DATABASE_NAME'] = 'tattle';
\$GLOBALS['DATABASE_USER'] = 'homestead';
\$GLOBALS['DATABASE_PASS'] = 'secret';
\$GLOBALS['TATTLE_DOMAIN'] = 'http://tattle.local';
?>" > /home/vagrant/tattle/inc/config.override.php

# Note the leading space prevents this from landing in bash history
 mysql -hlocalhost -uhomestead -psecret tattle < graphite_tattle_schema_alpha.sql --verbose

echo "Setup crontab and other root actions ####################################"
sudo su;
echo "* * * * * curl 127.0.0.1/processor.php" > /etc/cron.d/tattle

# super hacky but necessary because tattle sometimes uses short php tags
echo "
short_open_tag = On" >> /etc/php/7.0/fpm/php.ini

service php7.0-fpm reload
exit; # exit sudo

# Final Directions                                                              
echo "Add the following lines to your /etc/hosts file so you can access the"
echo "vagrant box tattle website from localhost"
echo "192.168.20.10   tattle.local"
echo ""
echo "Then you can just visit http://tattle.local in your browser" 
