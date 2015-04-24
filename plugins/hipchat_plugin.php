<?php

plugin_listener('plugin_settings', 'hipchat_settings');
plugin_listener('send_methods', 'hipchat_send_methods');
plugin_listener('plugin_user_settings', 'hipchat_user_settings');

function hipchat_settings() {
    return array(
		'hipchat_apikey' => array('friendly_name' => 'HipChat v2 API Key',
                                     'default' => '',
                                     'type' => 'string'),
		'hipchat_room' => array('friendly_name' => 'HipChat Room to Notify',
                                     'default' => '',
                                     'type' => 'integer'),
		'hipchat_warning_color' => array('friendly_name' => 'Hipchat Color for Warnings',
                                    'default' => 'yellow',
                                    'type' => 'string'),
		'hipchat_error_color' => array('friendly_name' => 'Hipchat Color for Errors',
                                    'default' => 'red',
                                    'type' => 'string'),
		'hipchat_ok_color' => array('friendly_name' => 'Hipchat Color for OK',
                                    'default' => 'green',
                                    'type' => 'string'),
		'hipchat_notify' => array('friendly_name' => 'Hipchat Plugin Enabled',
                                    'default' => true,
                                    'type' => 'bool')
	);
}

function hipchat_user_settings() {
  return array(
    'hipchat_user' => array(
      'friendly_name' => 'HipChat @ mention name',
      'default' => '',
      'type' => 'string'
    )
  );
}

function hipchat_send_methods() {
      return array(
        'hipchat_notify' => 'HipChat Group',
        'hipchat_user_notify' => 'HipChat User'
      );
}

// Message is being sent to the global HipChat Group
function hipchat_notify($check, $check_result, $subscription) {
    return hipchat_master_notify($check, $check_result, $subscription, false);
}

// Message is being sent to the user via @Mention
function hipchat_user_notify($check, $check_result, $subscription) {
  return hipchat_master_notify($check, $check_result, $subscription, true);
}

// Actuall send the message
function hipchat_master_notify($check, $check_result, $subscription,$toUser=true) {
    global $status_array;
	  global $debug;

    if(!is_callable('curl_init')){
        fCore::debug("!!! WARNING !!! function curl_init() not found, probably php-curl is not installed");
    }

    $state = $status_array[$check_result->getStatus()];

    if (strtolower($state) == 'ok') {
        $color = sys_var('hipchat_ok_color');
    } elseif (strtolower($state) == 'warning') {
        $color = sys_var('hipchat_warning_color');
    } elseif (strtolower($state) == 'error') {
        $color = sys_var('hipchat_error_color');
    }

    $url = $GLOBALS['TATTLE_DOMAIN'] . '/' . CheckResult::makeURL('list',$check_result);

    $data = array(
        'color' => $color,
        'notify' =>  ((sys_var('hipchat_notify') == 'true' ) ? true : false),
        'message_format' => 'html',
        'message' => "<b>" . $check->prepareName() . "</b><br />The check returned: {$check_result->getValue()}<br />View Alert Details : <a href=\"" . $url . "\">" . $url . "</a>"
	  );

    if ($debug && $toUser == false) {
			$url = 'https://api.hipchat.com/v2/room?auth_token=' . sys_var('hipchat_apikey');
		  $c = curl_init();
		  curl_setopt($c, CURLOPT_URL, $url);
		  curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

		  fCore::debug("Rooms: " . curl_exec($c) . "\n", FALSE);
		  fCore::debug("URL: " . 'https://api.hipchat.com/v2/room/' . strtolower(sys_var('hipchat_room')) . '/notification?auth_token=' . sys_var('hipchat_apikey') . "\n", FALSE);
		  fCore::debug("Data: " . print_r($data, true) . "\n", FALSE);
    }

    if ( $toUser == false ) {
      $url = 'https://api.hipchat.com/v2/room/' . strtolower(sys_var('hipchat_room')) . '/notification?auth_token=' . sys_var('hipchat_apikey');
    } else {
      $url = 'https://api.hipchat.com/v2/user/' .usr_var('hipchat_user', $subscription->getUserId()). '/message?auth_token=' . sys_var('hipchat_apikey');
    }
    fCore::debug("HipChat Calling: $url", FALSE);
  	$c = curl_init();
  	curl_setopt($c, CURLOPT_URL, $url);
  	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
  	curl_setopt($c, CURLOPT_HTTPHEADER, array(
      	'Content-Type: application/json',
      	'Content-Length: ' . strlen(json_encode($data)))
  	);
  	curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($data));

  	$response = curl_exec($c);

  	if ($response === false) {
      	fCore::debug("Curl error: " . curl_error($c) . "\n", FALSE);
  	}

    echo "\n\nResponse: " . curl_getinfo($c, CURLINFO_HTTP_CODE)  . ' - ' . $response . "\n\n";
}
