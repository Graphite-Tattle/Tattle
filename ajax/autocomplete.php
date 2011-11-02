<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/tattle/inc/init.php');

if ( $GLOBALS['SOURCE_ENGINE'] == "GANGLIA" ) {

    $json = file_get_contents($GLOBALS['GANGLIA_URL'] . "/tattle_autocomplete.php?term=" . $_REQUEST['term']);
    print $json;

} else {

    $dir = new fDirectory(WHISPER_DIR);
    
    $path = str_replace('.', '/' ,$_REQUEST['term']);
    
    //echo "Path is : $path";
    $directories = $dir->scanRecursive($path. '*');
    $return_arr = array();
    foreach ($directories as $directory) {
        $return_arr[] = array('value' => str_replace('.wsp','',str_replace('/','.',str_replace(WHISPER_DIR,'',$directory->getPath()))));
    }

    /* Toss back results as json encoded array. */
    echo json_encode($return_arr);

}
