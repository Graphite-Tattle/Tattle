<?php

plugin_listener('plugin_settings', 'pagerduty_settings');
plugin_listener('send_methods', 'pagerduty_send_methods');

function pagerduty_settings() {
    return array(
    'pagerduty_servicekey' => array('friendly_name' => 'PagerDuty Service Key',
                                     'default' => '',
                                     'type' => 'string'),
    'pagerduty_subject' => array('friendly_name' => 'Pager Duty Alert Subject',
                                    'default' => 'Tattle : {check_name} is {check_state}',
                                    'type' => 'string'));
}

function pagerduty_send_methods() {
      return array('pagerduty_notify' => 'PagerDuty');
}

function pagerduty_notify($check, $check_result) {
    global $status_array;
    $state = $status_array[$check_result->getStatus()];
    if ($state == 'OK') {
        $event_type = 'resolve';
    } else {
        $event_type = 'trigger';
    }
    $data = array(
        'service_key' => sys_var('pagerduty_servicekey'),
        'incident_key' => 'tattle_' . $check->getCheckId(),
        'event_type' => $event_type,
        'description' => str_replace(array('{check_name}', '{check_state}'), array($check->prepareName(), $state), sys_var('pagerduty_subject')),
        'details' => array('state' => $state, 'current_value' => $check_result->prepareValue(), 'error_level' => $check->getError(), 'warning_level' => $check->getWarn())
       );
    $ctx = stream_context_create(array('http' => array(
        'method' => 'POST',
        'header' => 'Content-Type:application/x-www-form-urlencodedi\r\n',
        'content' => json_encode($data)
         )));
    file_get_contents('https://events.pagerduty.com/generic/2010-04-15/create_event.json', null, $ctx);
}
