<?

function carrier_pidgin_config(){
  return array('name' => 'Carrier Pidgin (example)', 'settings'=>array('Phone Number' => 'string','Carrier' => 'int'));
}

function carrier_pidgin_settings(){
}

//carrier_pidgin plugin
function carrier_pidgin_send_notification($notifications) {
  echo "notify via carrier_pidgin : fly birdies fly!";
}
