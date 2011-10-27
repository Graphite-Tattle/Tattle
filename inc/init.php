<?
// Include main class loading config file`
include realpath(__DIR__ . '/config.php');
include realpath(__DIR__ . '/constructor_functions.php');
include realpath(__DIR__ . '/functions.php');

//Set the Template root, and set the header and footer
$tmpl = new fTemplating(realpath(__DIR__  . '/views/'));
$tmpl->set('header', 'header.php');
$tmpl->set('footer', 'footer.php');

//Set DB connection (using flourish it isn't actually connected to until the first use)
$mysql_db  = new fDatabase('mysql', $database_name, $database_user, $database_password);

//Connect the db to the ORM functions
fORMDatabase::attach($mysql_db);

//Start the Flourish Session
fSession::open();
