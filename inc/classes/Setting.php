<?
class Setting extends fActiveRecord
{
    protected function configure()
    {
    }
       static function findAll($filter)
        {               
                                
       return fRecordSet::build(
          __CLASS__,            
          $filter,
          array()
          );
        }
    
    static public function getSystem($setting_name) {
      return Setting::getSetting('system',$setting_name); 
    }

    static public function getUser($setting_name,$user_id=NULL) {
      return Setting::getSetting('user',$setting_name,$user_id);
    } 

    static public function getSetting($type='system',$setting_name=NULL,$user_id=NULL) {
      if (!is_null($setting_name)) { 
        if (is_numeric($user_id) && $type == 'user') {
          $setting = Setting::findAll(array('type' => $type,'name' => $setting_name,'owner_id' => $user_id));
          //$value = $setting->getValue();
        } elseif ($type == 'system') {  
          $setting = Setting::findAll(array('type=' => $type,'name=' => $setting_name,'owner_id=' => '0'));
         // $value = $setting->getValue();
        }
        return $setting;
      } else { 
        return false;
      }
    }

    static public function makeURL($type, $setting_type=NULL,$setting_name=NULL,$user_id=NULL)
        {
                if (is_object($setting_type)) {
                   $setting_name = $setting_type->getName();
                   $setting_type = 'system';
                }
                if (is_null($setting_type)) { 
                  $setting_type = 'system';
                }
                if (!is_null($user_id)) {
                  $user_id_query = '&user_id=' . $user_id;
                } else {
                  $user_id_query = '';
                }
                switch ($type)
                {
                        case 'list':
                                return 'setting.php?action=list&setting_type=' . $setting_type . $user_id_query;
                        case 'add':
                                return 'setting.php?action=add&setting_name=' . $setting_name . '&setting_type=' . $setting_type . $user_id_query;
                        case 'edit':
                                return 'setting.php?action=edit&setting_name=' . $setting_name . '&setting_type=' . $setting_type . $user_id_query;
                        case 'delete':
                                return 'setting.php?action=delete&setting_name=' . $setting_name . '&setting_type=' . $setting_type . $user_id_query;
                }       
        }


}
