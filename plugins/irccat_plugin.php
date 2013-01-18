<?php

plugin_listener('plugin_settings', 'irccat_settings');
plugin_listener('plugin_user_settings', 'irccat_user_settings');
plugin_listener('send_methods', 'irccat_send_methods');

function irccat_settings() {
    return array(
    'irccat_hostname' => array('friendly_name' => 'IRCcat hostname',
                            'default' => '127.0.0.1',
                            'type' => 'string'),
    'irccat_subject' => array('friendly_name' => 'IRCcat Subject',
                            'default' => 'Tattle {check_state} Alert for "{check_name}"',
                            'type' => 'string'),
    'irccat_port' => array('friendly_name' => 'IRCcat Port',
                            'default' => '12345',
                            'type' => 'int'));
}

function irccat_user_settings() {
    return array(
        'irccat_ircnick' => array('friendly_name' => 'IRCcat Nickname',
                            'default' => '',
                            'type' => 'string'),
        'irccat_channel' => array('friendly_name' => 'IRCcat Channel',
                            'default' => '',
                            'type' => 'string'));
}

function irccat_send_methods() {
      return array('irccat_notify' => 'IRCcat');
}

function irccat_notify($check, $check_result, $subscription) {
    global $status_array;
    $state = $status_array[$check_result->getStatus()];
    if ($state == 'OK') {
        $event_type = 'resolve';
    } else {
        $event_type = 'trigger';
    }

    $user = new User($subscription->getUserId());
    $irccat_channel = usr_var('irccat_channel', $user->getUserId());
    $irccat_ircnick = usr_var('irccat_ircnick', $user->getUserId());

    if (!empty($irccat_channel)) {
        $irc_target = $irccat_channel;
    } elseif (!empty($irccat_ircnick)) {
        $irc_target = '@' . $irccat_ircnick;
    } else {
        echo "No IRC Channel or Nickname selected for this users check " . $user->getUserId() . "\n";
        return false;
    }
    $message = $irc_target . " " . str_replace(array('{check_name}', '{check_state}'), array($check->prepareName(), $state), sys_var('irccat_subject')) .
        " Current : " . $check_result->prepareValue() . ", Error : " . $check->getError() . ", Warning : " . $check->getWarn();

    $fp = fsockopen(sys_var('irccat_hostname'),sys_var('irccat_port'), $errno, $errstr, 30);
    if (!$fp) {
        echo "$errstr ($errno)<br />\n";
    } else {
        stream_set_timeout($fp, 4);
        fwrite($fp, $message);
        fclose($fp);
    }
}
