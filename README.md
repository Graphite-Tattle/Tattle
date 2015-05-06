Tattle  (formely Graphite-Tattle)
======

A self service alerting and dashboard frontend for [graphite][graphite] and [ganglia][ganglia]

This tool was first presented at [EscConf][escconf] during a presentation by drowe from [Wayfair][wayfair]

Concepts
--------

### Checks
  A Check is a [graphite][graphite] or [ganglia][ganglia] target in combination with a user defined Error and Warning threshold.

### Subscriptions
  A Subscription is a users signing up for to be alerted by a plugin if the Check reaches the Error or Warning state. A user can have multiple subscriptions to an alert based on different threshold and plugins. (Example : SMS for Error, and Email for Warning)

  There are several subscriptions means available out of the shelf: e-mail through SMTP, IRC through IRCcat, HipChat, PagerDuty, PushOver. In addition, it's quite easy to write your own plugin for other notification means.

### Alerts
  An Alert is the signal that the Check either passed it's defined Error or Warning threshold, or it's returned to the OK state from being in a bad state. The frequency of Alerts is defined by the Repeat Delay (in minutes), which can't be less than the frequency of the processing cronjob.

### Dashboards
  A Dashboard is a collection of pre-defined graphs that allows for self service creation and has a fullscreen option.

### Graph
  A Graph in Tattle is a combination of "lines" which make up the graph. A graph can have one or more lines, and can set the display type of the graphs (Stacked or Not) and has a weight for ordering on the dashboard.

### Lines
  Lines are a combination of an Alias, a graphite Target, and a color.

Screen Shots
-----------------------------

* [Threshold Checks](./screenshots/tattle-threshold-checks.png)
* [Threshold Check Edit](./screenshots/tattle-check-edit-page.png)
* [Predictive Checks](./screenshots/tattle-predictive-checks.png)
* [Alert Subscriptions](./screenshots/tattle-subscriptions-page.png)
* [Alerts Overview Page](./screenshots/tattle-alerts-page.png)
* [Alert Page](./screenshots/tattle-alert-page.png)

Installation Requirements
-----------------------------

* PHP
* MySQL
* Lighttpd / NGINX / Apache
* [Flourishlib][flourishlib] for the PHP framework
* [bootstrap][bootstrap] for the HTML/CSS framework
* http access to a [graphite][graphite] or [ganglia][ganglia] installation
* php-curl for some alerting modules

Installation and Configuration
-----------------------------
* Import .sql file to create database and tables

* Create a session storage folder for flourishlib

* Create a file called inc/config.override.php so that upgrades don't blow away your config, you can check inc/config.php content for other available settings:

    ```
    <?
    $GLOBALS['DATABASE_HOST'] = '127.0.0.1';
    $GLOBALS['DATABASE_NAME'] = 'tattle';
    $GLOBALS['DATABASE_USER'] = 'dbuser';
    $GLOBALS['DATABASE_PASS'] = 'dbpass';
    $GLOBALS['GRAPHITE_URL'] = 'http://graph';
    ?>
    ```

* Create a logs folder which is writable by your webserver user

* Setup cronjob to to run processor.php. This script can be theoricaly run either as a cli or through the web server. Even though cli maybe required depending on your plugins and their required permissions, it has been reported to be easier to configure through the web server, e.g. with such a crontab entry:
    ```
    * * * * * curl 127.0.0.1/Graphite-Tattle/processor.php
    ```

* Register via the web interface. (The first user registered is the admin currently prior to us implementing any roles, and other permissions)


If you are on EL6 or a recent Fedora , make sure your php.ini has short_open_tag = off commented or you will get bogus output.

Dashboard Cleanurls
-----------
If you have apache, with mod_rewrite enabled and allow .htaccess files you can try the new Clean Dashboard urls.
Initial urls look like this:

```
http://localhost/dash/1/500/300
```

The second parameter should be replaced with the dashboard id you want to see.
The third parameter represents the heigt of the individual graphs.
The fourth parameter represents the width of the individual graphs

HTTP Auth Based User Accounts
-----------
If you are already using Web Server based authentication, then you can tell Tattle to use those credentials instead of keeping two sets of user accounts.

just set the following config variable to true in your config.override.php file:
```
$GLOBALS['ALLOW_HTTP_AUTH'] = true;
```

Reason for creation
-----------

[StatsD][statsd] from the team over at [Etsy][etsy] added a simple Dev and Ops friendly way to send metrics to graphite.
[graphite][graphite] makes graphing metrics and data self serve and simple for anyone.

With this tag team in our environment alerting seemed to be the weakest link from an adhoc/self service perspective which is where the idea
for Tattle came from.

Caution!
----------
This project is still in an Alpha status and not feature complete or ready for full production use yet.
Any help smoothing out the edges and adding additional features / functions would be greatly appreciated!

If you're having strange SQL issues, make sure you are using the most recent schema

How to Contribute
---------------------

You're interested in contributing to Tattle? Sweet!

fork Tattle from here: http://github.com/Graphite-Tattle/Tattle

1. Clone your fork
2. Hackit up
3. Push the branch up to GitHub
4. Send a pull request to the Graphite-Tattle/Tattle project.

We'll do our best to get your changes in as soon as possible!

[graphite]: http://graphite.wikidot.com
[ganglia]: http://ganglia.sourceforge.net/
[etsy]: http://www.etsy.com
[statsd]: https://github.com/etsy/statsd/
[bootstrap]: http://twitter.github.com/bootstrap/
[flourishlib]: http://flourishlib.com
[escconf]: http://escconf.com
[wayfair]: http://engineering.wayfair.com/


Contributors
---------------------
In lieu of a list of contributors, check out the commit history for the project:
https://github.com/Graphite-Tattle/Tattle/graphs/contributors
Though special shout out to [jpatapoff](https://github.com/jpatapoff/Graphite-Tattle) since he helped a lot, but his commits weren't attributed due to manually merging and to [f80](https://github.com/f80) who did all his commits with an email address no longer associated to his account.

