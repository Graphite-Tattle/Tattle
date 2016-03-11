#!/bin/bash

# This file runs after the vagrant machine is provisioned via `vagrant up`
echo
echo "Executing homestead extra provisioning ##################################"

echo
echo "Installing ubuntu extras ################################################"
sudo apt-get install -y ack-grep;

echo
echo "Run composer install and do database prep ###############################"
cd tattle
composer install

# TODO: Instead, have a file config.vagrant.php users can edit and copy it
echo "<?
// DATABASE SETTINGS
\$GLOBALS['DATABASE_HOST'] = '127.0.0.1';
\$GLOBALS['DATABASE_PORT'] = '3306';
\$GLOBALS['DATABASE_NAME'] = 'tattle';
\$GLOBALS['DATABASE_USER'] = 'homestead';
\$GLOBALS['DATABASE_PASS'] = 'secret';
\$GLOBALS['TATTLE_DOMAIN'] = 'http://tattle.local';
?>" > /home/vagrant/tattle/inc/config.override.php

# leading space prevents this from landing in bash history
 mysql -hlocalhost -uhomestead -psecret tattle < graphite_tattle_schema_alpha.sql --verbose

echo
echo "Setup crontab and other root actions ####################################"
sudo su;
echo "* * * * * curl 127.0.0.1/processor.php" > /etc/cron.d/tattle

# super hacky but necessary because tattle sometimes uses short php tags
echo "
short_open_tag = On" >> /etc/php/7.0/fpm/php.ini

service php7.0-fpm reload;

echo
echo "Final reminders #########################################################"
echo "Add this line to your /etc/hosts file to access the vagrant tattle website from localhost:"
echo "192.168.10.20   tattle.local"
echo ""
echo "Then you can just visit http://tattle.local in your browser"
echo "Note: You must not have another web server running on port 80 which would need to be stopped first"
exit; # exit sudo
