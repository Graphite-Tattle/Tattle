<?php
include '../inc/init.php';

$term = fRequest::get('term','string');

if ($GLOBALS['PRIMARY_SOURCE'] == 'GANGLIA') {
  if ($GLOBALS['GANGLIA_URL'] != '') {
    $json = file_get_contents($GLOBALS['GANGLIA_URL'] . '/tattle_autocomplete.php?term=' . $term);
    print $json;
  }
} else {
  $dir = new fDirectory($GLOBALS['WHISPER_DIR']);
  $path = str_replace('.', '/' ,fRequest::get('term','string'));
  $directories = $dir->scanRecursive($path. '*');
  $return_arr = array();
  foreach ($directories as $directory) {
    $return_arr[] = array('value' => str_replace('.wsp','',str_replace('/','.',str_replace($GLOBALS['WHISPER_DIR'],'',$directory->getPath()))));
  }
  print json_encode($return_arr);
}
