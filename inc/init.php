<?
// Include main class loading config file`i
$root_path = dirname(__FILE__);
include $root_path . '/includes.php';
include $root_path . '/config.php';
include $root_path . '/constructor_functions.php';
include $root_path . '/functions.php';

//Set the Template root, and set the header and footer
$tmpl = new fTemplating($root_path . '/views/');

$tmpl->enableMinification('development', dirname(__FILE__) . '/../js_cache/',dirname(__FILE__) . '/..');

$tmpl->add('css','/bootstrap/bootstrap.min.css'); 
$tmpl->add('css','/assets/css/jquery-ui.css');

$tmpl->add('js','/assets/js/jquery.min.js'); 
$tmpl->add('js','/assets/js/jquery-ui.min.js'); 
$tmpl->add('js','/assets/js/jquery.collapsible.js'); 
$tmpl->add('js','/assets/js/jquery.graphite.js');

$tmpl->add('js','/bootstrap/js/bootstrap-modal.js');
$tmpl->add('js','/bootstrap/js/bootstrap-twipsy.js');
$tmpl->add('js','/bootstrap/js/bootstrap-popover.js');


$tmpl->set('header', 'header.php');
$tmpl->set('footer', 'footer.php');

//Set DB connection (using flourish it isn't actually connected to until the first use)
$mysql_db  = new fDatabase('mysql', $database_name, $database_user, $database_password);

//Connect the db to the ORM functions
fORMDatabase::attach($mysql_db);

//Start the Flourish Session
fSession::open();
