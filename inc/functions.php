<?

function subarray_median($data_array) {
  foreach ($data_array as $value) {
    $temp_array[] = $value[0]; 
  }
  sort($temp_array);
  $count = count($temp_array); 
  $middleval = floor(($count-1)/2);
  if($count % 2) { 
    $median = $temp_array[$middleval];
  } else { 
    $low = $temp_array[$middleval];
    $high = $temp_array[$middleval+1];
    $median = (($low+$high)/2);
  }
  return $median;
}

function usr_var($setting_name,$user_id=NULL) {
  try{ 
    $setting = New Setting(array('name' => $setting_name,'owner_id' => $user_id));
    $value = $setting->getValue();
  } catch (fNotFoundException $e) {
    $setting = $GLOBALS['default_plugin_user_settings'][$setting_name];
    $value = $setting['default'];
  }
  return $value; 
}
 
function sys_var($setting_name) {
  try{
    $setting = New Setting(array('name' => $setting_name,'owner_id' => 0));
    $value = $setting->getValue();
  } catch (fNotFoundException $e) {
    $setting = $GLOBALS['default_plugin_settings'][$setting_name];
    $value = $setting['default']; 
  }   
  return $value;
}

function plugin_hook() {
  $arg_count = func_num_args();
  $args = func_get_args();
  $hook = array_shift($args);
  if(!isset($GLOBALS['hooks'][$hook])) { 
    return;
  }
  foreach($GLOBALS['hooks'][$hook] as $func){
    $args = $func($args);
  }
  return $args;
}

function plugin_listener($hook,$function){
  $GLOBALS['hooks'][$hook][] = $function;
}

function subarray_average($data_array) {
  $count = count($data_array); 
  $total = '';
  foreach ($data_array as $value) {
    $total = $total + $value[0]; 
  }
  $average = ($total/$count); 
  return $average;
}
