<?php
include '../inc/init.php';

$term = fRequest::get('term','string');

if ($GLOBALS['PRIMARY_SOURCE'] == 'GANGLIA') {
  if ($GLOBALS['GANGLIA_URL'] != '') {
    $json = file_get_contents($GLOBALS['GANGLIA_URL'] . '/tattle_autocomplete.php?term=' . $term);
    print $json;
  }
} else {
  $path = str_replace('.', '/' ,fRequest::get('term','string'));
  $return_arr = array();
  if ($GLOBALS['GRAPHITE_AUTOCOMPLETE_RECURSIVE'] == true) {
    $dir = new fDirectory($GLOBALS['WHISPER_DIR']);
    $directories = $dir->scanRecursive($path. '*');
  } else {
    $dir = new fDirectory($GLOBALS['WHISPER_DIR'] . $path);
    $directories = $dir->scan();
  }
  foreach ($directories as $directory) {
    $return_arr[] = array('value' => str_replace('.wsp','',str_replace('/','.',str_replace($GLOBALS['WHISPER_DIR'],'',$directory->getPath()))));
  }
  print json_encode($return_arr);
}
