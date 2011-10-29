<?php
include dirname(__FILE__) . '/../inc/init.php');
$dir = new fDirectory(WHISPER_DIR);

$path = str_replace('.', '/' ,fRequest::get('term','string'));

//echo "Path is : $path";
$directories = $dir->scanRecursive($path. '*');
$return_arr = array();
foreach ($directories as $directory) {
    $return_arr[] = array('value' => str_replace('.wsp','',str_replace('/','.',str_replace(WHISPER_DIR,'',$directory->getPath()))));
}

/* Toss back results as json encoded array. */
echo json_encode($return_arr);
