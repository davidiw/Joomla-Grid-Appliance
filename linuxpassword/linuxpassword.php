<?php
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgUserLinuxPassword extends JPlugin
{
  function plgUserLinuxPassword(& $subject, $config) {
    parent::__construct($subject, $config);
  }

  function onLoginUser($user, $options = array())
  {
    $this->insert_linux_pwd($user['username'], $user['password'], false);
    return true;
  }

  function onAfterStoreUser($user, $isnew, $success, $msg)
  {
    global $mainframe;
    $this->insert_linux_pwd($user['username'], $_POST['password'], true);
  }

  function md5crypt($password){
    $base64_alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    $salt = '$1$';
    for($i = 0; $i < 9; $i++){
      $salt .= $base64_alphabet[rand(0, 63)];
    }
    return crypt($password, $salt. '$');
  }

  function insert_linux_pwd($username, $password, $over_write)
  {
    $db = &JFactory::getDBO();
    $user_db = 'jos_users';

    if ($over_write == false){
      $query = 'SELECT password FROM #__linux_passwords WHERE username=\''.$username.'\'';
      $db->setQuery($query);
      $result = $db->loadResult();
      if($result !== NULL) {
        throw new Exception($result);
        return;
      }
    }

    $crypt_pwd = $this->md5crypt($password);
    $pwd_insert_query = 'INSERT INTO jos_linux_passwords (username, password)'.
      'VALUES (\''.$username.'\', \''.$crypt_passwd.'\')'.
      'ON DUPLICATE KEY UPDATE password=\''.$crypt_passwd.'\'';
    throw new Exception($pwd_insert_query);
    $db->Execute($pwd_insert_query);
  }
}
