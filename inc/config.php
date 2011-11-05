<?php

// DATABASE SETTINGS
$GLOBALS['DATABASE_NAME'] = 'graphite_tattle';
$GLOBALS['DATABASE_USER'] = 'root';
$GLOBALS['DATABASE_PASS'] = '';

// GRAPHITE and GANGLIA Settings
$GLOBALS['PRIMARY_SOURCE'] = 'GRAPHITE'; //Currently can be GRAPHITE or GANGLIA
$GLOBALS['GRAPHITE_URL'] = 'http://localhost:8000';
$GLOBALS['GANGLIA_URL'] = 'http://localhost:8000/ganglia2';

// Graph Styling
$GLOBALS['ERROR_COLOR'] = 'red';
$GLOBALS['WARN_COLOR'] = 'yellow';
$GLOBALS['GRAPH_WIDTH'] = '586';
$GLOBALS['GRAPH_HEIGHT'] = '308';
$GLOBALS['WHISPER_DIR'] = '/opt/graphite/storage/whisper/';

// Flourish Related Settings
$GLOBALS['FLOURISHLIB_PATH'] = '/inc/flourish/'; 
$GLOBALS['SESSION_FILES'] = '/tmp';

// Bootstrap Settings
$GLOBALS['BOOTSTRAP_PATH'] = '/bootstrap/';


// Allow loading GLOBAL overrides
if(file_exists(  TATTLE_ROOT . "/inc/config.override.php" ) ) {
  include_once  TATTLE_ROOT . "/inc/config.override.php";
}

//Load in plugin files
$plugin_settings = array();
foreach (glob("plugins/*_plugin.php") as $plugin) {
  include_once($plugin);
  $plugin_name = str_replace(array('plugins/', '_plugin.php'), '', $plugin);
  $plugin_config = $plugin_name . '_config';
  $plugin_engine = $plugin_name . '_engine';
  $plugin_notify = $plugin_name . '_notify';

  if (function_exists($plugin_config)) {
    $plugin_settings[$plugin_name] = $plugin_config();
    if (function_exists($plugin_notify)) {
      $send_methods[$plugin_name] = $plugin_settings[$plugin_name]['name'];
    }
    if (function_exists($plugin_engine)) {
      $data_engine[$plugin_name] = $plugin_settings[$plugin_name]['name'];
    }
  }
}

// Check to make sure the session folder exists 
$config_error = '';
$config_exit = false;

try {
  //Set DB connection (using flourish it isn't actually connected to until the first use)
  $mysql_db  = new fDatabase('mysql', $GLOBALS['DATABASE_NAME'],$GLOBALS['DATABASE_USER'], $GLOBALS['DATABASE_PASS']);
  // Please note that calling this method is not required, and simply
  // causes an exception to be thrown if the connection can not be made
  $mysql_db->connect();
} catch (fAuthorizationException $e) {
  $config_error = "DB error : " . $e->getMessage();
  $config_exit = true;
}

//Connect the db to the ORM functions
fORMDatabase::attach($mysql_db);



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
$breadcrumbs = array();
$breadcrumbs[] = array('name' => 'Home', 'url' => '#', 'active'=> false);

error_reporting(E_STRICT | E_ALL);
fCore::enableErrorHandling('html');
fCore::enableExceptionHandling('html');

fTimestamp::setDefaultTimezone('America/New_York');

fAuthorization::setLoginPage(User::makeURL('login'));
fAuthorization::setAuthLevels(
                 array('admin' => 100,
                       'user'  => 50,
                       'guest' => 25
                 )
                );
// This prevents cross-site session transfer
fSession::setPath($GLOBALS['SESSION_FILES']);

