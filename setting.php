<?php
include 'inc/init.php';

fAuthorization::requireLoggedIn();

fRequest::overrideAction();

$action = fRequest::getValid('action', array('list', 'add', 'edit', 'delete', 'view'));

$setting_name = fRequest::get('setting_name', 'string');
$setting_type = fRequest::getValid('setting_type',array('system','user'));
$user_id = fRequest::get('user_id','integer');

if ($setting_type == 'user') {
  if ($user_id > 0) {
    $owner_id = $user_id;
  } else {
    $owner_id = fSession::get('user_id');
  }    
} else {  
  $owner_id = 0;
}

if ('delete' == $action) {
   $class_name = 'Setting';
  try {
    $obj = new Setting(array('name' => $setting_name,'owner_id' => $owner_id));
    $delete_text = 'Are you sure you want to delete this setting : <strong>' . $obj->getFriendlyName() . '</strong>?';
    if (fRequest::isPost()) {
      fRequest::validateCSRFToken(fRequest::get('token'));
      $obj->delete();
      fMessaging::create('success', fURL::get(),
                         'The setting ' . $obj->getFriendlyName() . ' was successfully deleted');
      fURL::redirect(Setting::makeUrl('list',$setting_type,NULL,$owner_id));      
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', fURL::get(),
                       'The setting requested could not be found');
    fURL::redirect(Setting::makeUrl('list'));
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());
  }
 
  include VIEW_PATH . '/delete.php';
 
// --------------------------------- //
} elseif ('edit' == $action) {
  try {
    $setting = new Setting(array('name' => $setting_name,'owner_id' => $owner_id));
    if (fRequest::isPost()) {
      $setting->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $setting->store();
			
      fMessaging::create('affected', fURL::get(), $setting->getFriendlyName());
      fMessaging::create('success', fURL::get(), 
                         'The setting ' . $setting->getFriendlyName(). ' was successfully updated');
      fURL::redirect(Setting::makeURL('list',$setting_type,NULL,$owner_id));
    }
  } catch (fNotFoundException $e) {
    fMessaging::create('error', fURL::get(), 
                       'The Setting requested, ' . fHTML::encode($setting_name) . ', could not be found');	
    fURL::redirect(Setting::makeUrl('list'));
  } catch (fExpectedException $e) {
    fMessaging::create('error', fURL::get(), $e->getMessage());	
  }

  include VIEW_PATH . '/add_edit_setting.php';
	
// --------------------------------- //
} elseif ('add' == $action) {
  $setting = new Setting();
  if ('user' == $setting_type) {
    $list_plugin_settings = $plugin_user_settings;
  } else {
    $list_plugin_settings = $plugin_settings;
  }
  if (!array_key_exists($setting_name,$list_plugin_settings)) {
    $setting_name = '';
  }
  $setting->setFriendlyName($list_plugin_settings[$setting_name]['friendly_name']);
  $setting->setName($setting_name);
  $setting->setPlugin('email');
  if ($setting_type == 'user') {
    $setting->setOwnerId($user_id);
    $setting->setType('user');
  } else {
    $setting->setOwnerId(0);
  }
  if (fRequest::isPost()) {	
    try {
      $setting->populate();
      fRequest::validateCSRFToken(fRequest::get('token'));
      $setting->store();
      $setting_url = Setting::makeUrl('list',$setting_type);
      fMessaging::create('affected', fURL::get());
      fMessaging::create('success', fURL::get(), 
                         'The setting ' . $setting->getFriendlyName() . ' was successfully created');
      fURL::redirect($setting_url);	
    } catch (fExpectedException $e) {
      fMessaging::create('error', fURL::get(), $e->getMessage());	
    }	
  } else {
    $setting->setValue($list_plugin_settings[$setting_name]['default']);
  }
  include VIEW_PATH . '/add_edit_setting.php';	
	
} else {
  if ('user' == $setting_type) {
    $current_plugin_user_settings = Setting::findAll(array('type=' => 'user','owner_id=' => $owner_id));
    foreach ($current_plugin_user_settings as $user_setting) {
      $plugin_user_settings[$user_setting->getName()]['value'] = $user_setting->getValue(); 
    }
  $list_plugin_settings = $plugin_user_settings;
  } else {
    $list_plugin_settings = $plugin_settings; 
  }
include VIEW_PATH . '/list_settings.php';

}
