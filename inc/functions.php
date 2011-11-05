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

function display_header() {
  print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
  print '<html xmlns="http://www.w3.org/1999/xhtml" lang="en-us">';
  print '<head><link type="text/css" href="assets/main.css" rel="stylesheet" media="screen" /></head>';
  print '<body><div><h2>Tattle</h2></div>';
  print '<div><a href="index.php">Home</a> | ';    
  print '<a href="edit_alert.php">Add Alert</a> | ';    
  print '<a href="check_status.php">Status Page</a> | ';    
  print '<a href="processor.php">Manual Poll</a></div>';    
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

function edit_alert($nid=0) {
  global $dblink, $username;
  if (can_edit($nid)) {
    $sql = 'SELECT * FROM checks WHERE nid =' . $nid . ';';
    $results = mysql_query($sql,$dblink);
    
  if (!$results) {
    print 'Results Error : ' . mysql_error(). "\n";
  } else {
    $row = mysql_fetch_assoc($results);
  }
   print '<form method="post">';
   if ($nid > 0) {
     print '<fieldset><legend>Edit Alert</legend><ol>';
   } else {
     print '<fieldset><legend>Add Alert</legend><ol>';  
   }
    
   print '<li><label for="form_title">title</label><input id="form_title" type="text" name="title" value="'. $row['title'] .'" /></li>';
   print '<li><label for="form_owner">owner</label><input type="text" name="owner" value="'. $row['owner'] .'" /></li>';
   print '<li><label for="form_target">target</label><input type="text" name="target" value="'. $row['target'] .'" /></li>';
   print '<li><label for="form_warn">warn</label><input type="text" name="warn" value="'. $row['warn'] .'" /></li>';
   print '<li><label for="form_error">error</label><input type="text" name="error" value="'. $row['error'] .'" /></li>';
   print '<li><label for="form_sample">sample</label><select name="sample">';
   print '<option value="-5minutes"'. ($row['sample'] == '-5minutes' ? ' selected' : '') .'>5 minutes</option>';
   print '<option value="-10minutes"'. ($row['sample'] == '-10minutes' ? ' selected' : '') .'>10 minutes</option></select></li>';
   print '<li><label for="form_baseline">baseline</label><select name="baseline"><option value="average">average</option><option value="mean">mean</option></select></li>';
   print '<li><label for="form_over_under">Calculation</label><select name="over_under"><option value="0">Over</option><option value="1">Under</option></select></li>';   
   print '<li><label for="form_over_under">Visibility</label><select name="visibility"><option value="0">Public</option><option value="1">Private</option></select></li>';   
   if ($nid > 0) {
     print '<input type="hidden" name="nid" value="' . $nid . '" />';
   }
   print '</ol></fieldset><p><input type="submit" value="Save" /></p></form>';
  }
}

function save_alert($post_data,$mode) {
  global $dblink, $username;
  if (count($post_data) > 8) {
    $title = validate_element($post_data,'title');
    $owner = validate_element($post_data,'owner');
    $target = validate_element($post_data,'target');
    $warn = validate_element($post_data,'warn');
    $error = validate_element($post_data,'error');
    $sample = validate_element($post_data,'sample');
    $baseline = validate_element($post_data,'baseline');
    $over_under = validate_element($post_data,'over_under');
    $visibility = validate_element($post_data,'visibility');
    if ($mode == 'edit') {
      $nid = validate_element($post_data,'nid');
      $sql = "UPDATE checks SET title='" . $title . "',owner='" . $owner . "',target='" . $target . "',warn='" . $warn . "',error='" . $error . "',sample='" . $sample . "',baseline='" . $baseline . "',over_under='" . $over_under. "', visibility='" . $visibility ."' WHERE nid = " . $nid .";";
    } else {
      $sql = "INSERT INTO checks (title,owner,target,warn,error,sample,baseline,over_under,visibility) VALUES ('" . $title . "','" . $owner . "','" . $target . "','" . $warn . "','" . $error . "','" . $sample . "','" . $baseline . "','" . $over_under. "','" . $visibility ."');";
    }
    $results = mysql_query($sql,$dblink);
    if (!$results) {
    } else {
      print 'Alert Updated';
    }
  }
}

function validate_element($data_array,$element) {
  global $messages_warn, $messages_errors;
  if (isset($data_array[$element])) {
      if ($element == 'visibility'){
        //public = 0
        //private = 1
        return ($data_array[$element] == 'on' ? 0 : 1);
      }
    //need to add all the field validation at some point
    return $data_array[$element];
  } else {
    $messages_errors .= 'Field ' . $element . ' missing data <br />';
    return false;
  }
}
function can_edit($nid) {
    //make this work
  return true;    
}

function display_alerts() {
  global $mysql_db, $username;
  $result = $mysql_db->query('SELECT * FROM checks');
  $rows_html = '';
   foreach ($result as $row) {
      $rows_html .= display_alert_row($row);
   }
  return $rows_html;
}

function generate_comparison_value($data, $alert_config){
  if ($alert_config->baseline == 'average') {
    $compare_value = subarray_average($data[0]->datapoints);
  } elseif ($alert_config->baseline == 'median') {
    $compare_value = subarray_median($data[0]->datapoints);
  }
 return $compare_value;
}

function validate_results($test_value, $alert_config) {
  if ($test_value >= $alert_config->error) { 
    fCore::debug('error state' . " $test_value\n",FALSE); 
    $state = 1;
  } elseif ($test_value >= $alert_config->warn) { 
    fCore::debug('warn state' . " $test_value\n",FALSE); 
    $state = 2;
  } else { 
    fCore::debug('all good ' . " $test_value\n",FALSE);
    $state = 0;
  }
  return $state;
}

function display_alert_row($alert_config) {
  $return = '<tr><td>' . make_graphite_link($alert_config, true)  . '</td>';
  $return .= '<td>' . $alert_config['owner'] . '</td>';
  $return .= '<td>' . $alert_config['target'] . '</td>';  
  $return .= '<td>' . $alert_config['warn'] . '</td>';
  $return .= '<td>' . $alert_config['error'] . '</td>';
  $return .= '<td>' . $alert_config['sample'] . '</td>';
  $return .= '<td>' . $alert_config['baseline'] . '</td>';
  $return .= '<td>' . $alert_config['over_under'] . '</td>';
  $return .= '<td>' . display_subscribers_count($alert_config['nid']) . '</td>';
  $return .= '<td>' . $alert_config['visibility'] . '</td>';
  $return .= '<td>' . action_buttons($alert_config) . '</td></tr>';
  return  $return;
}

function action_buttons($alert_config) {
  global $username;
  $results = '';
  if ($username == $alert_config['owner']) {
    $results .= '<a href="edit_alert.php?nid='. $alert_config['nid'] . '">Edit Alert</a> | ';
  }
  if (is_subscribed($username,$alert_config['nid'])) {
    $results .= '<a href="modify_subscription.php?nid='. $alert_config['nid'] . '&action=delete">Un-Subscribe</a> | ';
  } else {
    $results .= '<a href="modify_subscription.php?nid='. $alert_config['nid'] . '&action=add">Subscribe</a> | ';
  }

  return $results;
}

function is_subscribed($username,$nid) {
  return false;    
}

function in_object_key($value,$object) {
  if (is_object($object)) {
    foreach($object as $key => $item) {
      if ($value==$key) {
        return $key;
      }
    }
  } else {
    return false;
  }
}

function in_object($value,$object) {
  if (is_object($object)) {
    foreach($object as $key => $item) {
      if ($value==$item) return $key;
    }
  }
  return false;
}


function display_subscribers_count($nid){
    global $dblink;
   $sql = 'SELECT count(sub_nid) as count FROM subscriptions WHERE sub_nid = ' . $nid;
   $results = mysql_query($sql,$dblink);
   $rows_html = '';
   if (!$results) {
     print 'Results Error : ' . mysql_error(). "\n";
    return 0;
   } else {
     $row = mysql_fetch_assoc($results);
     return $row['count'];
  } 
}

function make_graphite_link($alert_config, $make_href = true, $href_title = false) {
  $link = $GLOBALS['GRAPHITE_URL'] . '?target=' . $alert_config['target'] . '&from=' . $alert_config['sample'];
  if ($make_href) {
    if ($href_title === false) {
      $title = $alert_config['title'];
    } else {
      $title = $href_title;
    }
    $link = '<a href="' . $link . '">' . $title . '</a>';
  }
 return $link;
}

function get_check_data($alert_config){
  $json_data = file_get_contents( $GLOBALS['GRAPHITE_URL']  . '?target=' . $alert_config->target . '&from=-5minutes&format=json');
  $data = json_decode($json_data);
  return $data;
}


function timestamp_diff($start,$end = false) {
    /*
    * For this function, i have used the native functions of PHP. It calculates the difference between two timestamp.
    *
    * Author: Toine
    *
    * I provide more details and more function on my website
    */

    // Checks $start and $end format (timestamp only for more simplicity and portability)
    if(!$end) { $end = time(); }
    if(!is_numeric($start) || !is_numeric($end)) { return false; }
    // Convert $start and $end into EN format (ISO 8601)
    $start  = date('Y-m-d H:i:s',$start);
    $end    = date('Y-m-d H:i:s',$end);
    $d_start    = new DateTime($start);
    $d_end      = new DateTime($end);
    $diff = $d_start->diff($d_end);
    // return all data
    $this->year    = $diff->format('%y');
    $this->month    = $diff->format('%m');
    $this->day      = $diff->format('%d');
    $this->hour     = $diff->format('%h');
    $this->min      = $diff->format('%i');
    $this->sec      = $diff->format('%s');
    return true;
} 
