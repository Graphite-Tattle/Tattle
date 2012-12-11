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

function median($data_array) {
  sort($data_array);
  $count = count($data_array);
  $middleval = floor(($count-1)/2);
  if($count % 2) {
    $median = $data_array[$middleval];
  } else {
    $low = $data_array[$middleval];
    $high = $data_array[$middleval+1];
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

function average($data_array) {
  $count = count($data_array);
  $total = '';
  foreach ($data_array as $value) {
    $total = $total + $value;
  }
  $average = ($total/$count);
  return $average;
}

/**
 * Returns the first item of the last item
 *
 * @param array $data_array Array of something
 * @return mixed the first item of the last item in the array
 */
function subarray_endvalue($data_array) {
    if ( is_array($data_array) ) {
        $lastDataPoint = end($data_array);
        return($lastDataPoint[0]);
    } else {
        return false;
    }
}

function subarray_standard_deviation($data_array) {
  //Find N
  $N = count($data_array);
  //Compute the mean for the distribution
  $mean = subarray_average($data_array);

  $sum_of_squared_differences = 0;

  foreach($data_array as $value) {
    //Compute the difference between each score and the mean
    $difference = $value[0] - $mean;
    //Square these differences
    $difference_squared = pow($difference,2);
    //Add up all the squared differences
    $sum_of_squared_differences = $sum_of_squared_differences + $difference_squared;
  }
  //Divide the sum of the squared differences by the number of cases, minus one
  $sd_squared = $sum_of_squared_differences / ($N - 1);
  //Take the square root of the result from line 5
  return sqrt($sd_squared);
}
?>
