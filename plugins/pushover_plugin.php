<?php

plugin_listener('plugin_settings', 'pushover_plugin_settings');
plugin_listener('plugin_user_settings','pushover_plugin_user_settings');
plugin_listener('send_methods', 'pushover_plugin_send_methods');

function pushover_plugin_settings() {
    return array(
        'pushover_plugin_application_token' => array(
            'friendly_name' => 'Pushover application token',
            'default' => '',
            'type' => 'string'
        ),
    );
}

function pushover_plugin_user_settings() {
    return array(
        'pushover_plugin_user_key' => array(
            'friendly_name' => 'Pushover user key',
            'default' => '',
            'type' => 'string'
        )
     );
}

function pushover_plugin_send_methods() {
    return array(
        'pushover_plugin_notify' => 'Pushover'
    );
}

function pushover_plugin_notify($check, $check_result, $subscription) {
    $user = new User($subscription->getUserId());
    
    global $status_array;
    $check_status = $status_array[$check_result->getStatus()];
    $check_name = $check->prepareName();
    $check_value = $check_result->prepareValue();
    $check_warning_level = $check->getWarn();
    $check_error_level = $check->getError();
    $check_type = $check->getType();

    $title = "$check_status for $check_name";

    $message = "Check returned: $check_value\n" .
               "Warning $check_type is: $check_warning_level\n" .
               "Error $check_type is: $check_error_level";

    $data = array(
        'token' => sys_var('pushover_plugin_application_token'), 
        'user' =>  usr_var('pushover_plugin_user_key', $user->getUserId()),
        'message' => $message,
        'title' => $title,
        'url' => $GLOBALS['TATTLE_DOMAIN'] . '/' . CheckResult::makeURL('list', $check_result),
    );

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );

    $result = file_get_contents("https://api.pushover.net/1/messages.json", false, stream_context_create($options));
}
