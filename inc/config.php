<?php

// DATABASE TYPE
// Right now, only 'mysql' is supported
$GLOBALS['DATABASE_TYPE'] = 'mysql';

// DATABASE SETTINGS
$GLOBALS['DATABASE_HOST'] = '127.0.0.1';
$GLOBALS['DATABASE_PORT'] = '3306';
$GLOBALS['DATABASE_NAME'] = 'tattle';
$GLOBALS['DATABASE_USER'] = 'dbuser';
$GLOBALS['DATABASE_PASS'] = 'dbpass';
$GLOBALS['TATTLE_DOMAIN'] = 'http://localhost';

// GRAPHITE and GANGLIA Settings
$GLOBALS['PRIMARY_SOURCE'] = 'GRAPHITE'; //Currently can be GRAPHITE or GANGLIA
$GLOBALS['GRAPHITE_URL'] = 'http://localhost:8000';
$GLOBALS['PROCESSOR_GRAPHITE_URL'] = ''; // defaults to GRAPHITE_URL
$GLOBALS['GRAPHITE_AUTOCOMPLETE_RECURSIVE'] = false;
$GLOBALS['GANGLIA_URL'] = 'http://localhost:8000/ganglia2';

// Graph Styling
$GLOBALS['ERROR_COLOR'] = 'red';
$GLOBALS['WARN_COLOR'] = 'yellow';
$GLOBALS['GRAPH_WIDTH'] = '586';
$GLOBALS['GRAPH_HEIGHT'] = '308';
$GLOBALS['WHISPER_DIR'] = '/opt/graphite/storage/whisper/'; // must end with /

// Flourish Related Settings
$GLOBALS['FLOURISHLIB_PATH'] = '/inc/flourish/';
$GLOBALS['SESSION_FILES'] = '/tmp';

// Processor Logging Files
$GLOBALS['PROCESSOR_LOG_PATH'] = TATTLE_ROOT . '/logs/';
// Bootstrap Settings
$GLOBALS['BOOTSTRAP_PATH'] = '/bootstrap/';

// Allow HTTP auth as user management
$GLOBALS['ALLOW_HTTP_AUTH'] = false;

// Number of elements per page (checks, alerts, subscriptions)
$GLOBALS['PAGE_SIZE'] = 15;

// Global variable for groups
$GLOBALS['DEFAULT_GROUP_ID'] = 1;

// Locale settings
$GLOBALS['TIMEZONE'] = 'America/New_York';

// The offset time for the threshold alerts
$GLOBALS['ALERTS_TIME_OFFSET'] = 1;

// Allow loading GLOBAL overrides
if(file_exists(  TATTLE_ROOT . "/inc/config.override.php" ) ) {
  include_once  TATTLE_ROOT . "/inc/config.override.php";
}

//Load in plugin files

$GLOBALS['hooks'] = array();

foreach (glob("plugins/*_plugin.php") as $plugin) {
  include_once($plugin);
}

// Check to make sure the session folder exists
$config_error = '';
$config_exit = false;

if ($GLOBALS['DATABASE_TYPE'] == 'mysql') {
  try {
    $db  = new fDatabase('mysql', $GLOBALS['DATABASE_NAME'], $GLOBALS['DATABASE_USER'], $GLOBALS['DATABASE_PASS'], $GLOBALS['DATABASE_HOST'], $GLOBALS['DATABASE_PORT']);
    // Please note that calling this method is not required, and simply
    // causes an exception to be thrown if the connection can not be made
    $db->connect();
  } catch (fAuthorizationException $e) {
    $config_error = "DB error : " . $e->getMessage();
    $config_exit = true;
  } catch (fConnectivityException $e) {
    $config_error = "DB error : " . $e->getMessage();
    $config_exit = true;
  }
} else {
    $config_error = "Unsupported database type : " . $GLOBALS['DATABASE_TYPE'];
    $config_exit = true;
}

if (!$config_exit) {
    //Connect the db to the ORM functions
    fORMDatabase::attach($db);

    $default_plugin_settings = plugin_hook('plugin_settings');
    $default_plugin_user_settings = plugin_hook('plugin_user_settings');

    $send_methods = plugin_hook('send_methods');
    $current_plugin_settings = Setting::findAll(array('type=' => 'system'));
    $plugin_settings = $default_plugin_settings;
    $plugin_user_settings = $default_plugin_user_settings;

    foreach ($current_plugin_settings as $setting) {
        $plugin_settings[$setting->getName()]['value'] = $setting->getValue();
    }
}

if (!is_dir(JS_CACHE)) {
  $config_error .="<br/>Tattle Error <br />" .
                  "Can't write to the js cache folder : " . JS_CACHE;
}

if (!is_dir($GLOBALS['SESSION_FILES']) || !is_writable($GLOBALS['SESSION_FILES'])){
 $config_error .="<br/>Tattle Error <br />" .
                  "Flourishlib Session path is not write-able. Path at : " . $GLOBALS['SESSION_FILES'];
  $config_error = true;
}

if ($config_exit) {
  print $config_error;
  exit;
}


$status_array = array('0' => 'OK', '1'   => 'Error', '2' => 'Warning');
$visibility_array = array('0'   => 'Public', '1' => 'Private');
$over_under_array = array('0'   => 'Over', '1' => 'Under');
$over_under_both_array = array('0'   => 'Over', '1' => 'Under', '2' => 'Both');
$average_median_array = array('average' => 'Average', 'median' => 'Median');
$regression_type_array = array('daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly');
$number_of_regressions_array = array('1' => '1','2' =>'2','3' =>'3','4' =>'4','5' =>'5','6' =>'6','7' =>'7','8' => '8','9' => '9','10' => '10', '11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15');
$breadcrumbs = array();
$breadcrumbs[] = array('name' => 'Home', 'url' => 'index.php', 'active'=> false);

error_reporting(E_STRICT | E_ALL);
fCore::enableErrorHandling('html');
fCore::enableExceptionHandling('html');

fTimestamp::setDefaultTimezone($GLOBALS['TIMEZONE']);

fAuthorization::setLoginPage(User::makeURL('login'));
fAuthorization::setAuthLevels(
                 array('admin' => 100,
                       'user'  => 50,
                       'guest' => 25
                 )
                );
// This prevents cross-site session transfer
fSession::setPath($GLOBALS['SESSION_FILES']);


if (!fAuthorization::checkLoggedIn()) {
  if ($GLOBALS['ALLOW_HTTP_AUTH'] && (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))) {
    unset($_SERVER['PHP_AUTH_PW']); //no need for a clear text password hanging around.
    try {
      $user = new User(array('username' => $_SERVER['PHP_AUTH_USER']));
      // Auto Register User
      fAuthorization::setUserToken($user->getEmail());
      fAuthorization::setUserAuthLevel($user->getRole());
      fSession::set('user_id',$user->getUserId());
      fSession::set('user_name',$user->getUsername());
    } catch (fNotFoundException $e) {

       if (fURL::getWithQueryString() != (TATTLE_WEB_ROOT . User::makeURL('add'))) {
        fMessaging::create('affected', User::makeURL('add'), $_SERVER['PHP_AUTH_USER'] );
        fMessaging::create('success', User::makeURL('add'),
                         'The user ' . $_SERVER['PHP_AUTH_USER'] . ' was successfully created');
        fURL::redirect(User::makeURL('add'));
     }
    }
  }
}
