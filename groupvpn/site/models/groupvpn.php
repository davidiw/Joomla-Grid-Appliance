<?php
// no direct access
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

class GroupVPNModelGroupVPN extends JModel {
  // List of global methods
  // Public
  static function loadGroups() {
    $db = & JFactory::getDBO();
    $query = "SELECT group_id, group_name, description, last_update FROM groupvpn";
    $db->setQuery($query);
    return $db->loadObjectList("group_id");
  }

  // Admin
  function storeGroup() {
    $groupvpn =& $this->getTable("groupvpn");
    $groupvpn->bind(JRequest::get("post"));
    $user =& JFactory::getUser();

    if($groupvpn->group_id) {
      if(!$this->isAdmin($groupvpn->group_id, $user->id)) {
        JError::raiseError(403, JText::_('Access Forbidden'));
      }
      $groupvpn->store();
    } else {
      if(!$groupvpn->store()) {
        JError::raiseWarning(500, JText::_('A similar group already exists...'));
        return false;
      }
      $db = & JFactory::getDBO();
      $query = "INSERT INTO groups (user_id, group_id, member, admin, reason, secret) VALUES ".
        "(".$user->id.", ".$groupvpn->group_id.", 1, 1, \"Creator\", \"".
        GroupVPNModelGroupVPN::getSecret()."\")";
      $db->Execute($query);

      $request = xmlrpc_encode_request("GenerateCACert", $groupvpn->group_name);
      $context = stream_context_create(array('http' => array(
          'method' => "POST",
          'header' => "Content-Type: text/xml",
          'content' => $request
      )));
      $file = file_get_contents("http://www.grid-appliance.org/components/com_groupvpn/mono/GroupVPN.rem", false, $context);
      $response = xmlrpc_decode($file);
      if(xmlrpc_is_fault($response)) {
        JError::raiseError(500, JText::_("xmlrpc: $response[faultString] ($response[faultCode])"));
      }
    }

    return $groupvpn->group_id;
  }

  // List of individual methods
  // Public / User
  function loadGroupMembers() {
    $groupvpn = $this->loadGroup();
    if(!$groupvpn) {
      return false;
    }

    $user =& JFactory::getUser();
    if($this->isAdmin($groupvpn->group_id, $user->id)) {
      $query = "SELECT reason, user_id, username, name, email, admin, request, member, revoked FROM groups ".
        "LEFT JOIN #__users on (groups.user_id = #__users.id) ".
        "WHERE group_id = ".$groupvpn->group_id;
    } else {
      $query = "SELECT reason, user_id, username, name, email, admin, revoked, member FROM groups ".
        "LEFT JOIN #__users on (groups.user_id = #__users.id) ".
        "WHERE group_id = ".$groupvpn->group_id;
    }

    $db = & JFactory::getDBO();
    $db->setQuery($query);
    return $db->loadObjectList("user_id");
  }

  function loadMyGroupInformation() {
    $user =& JFactory::getUser();
    $db = & JFactory::getDBO();
    $query = "SELECT groups.group_id, reason, request, member, admin, revoked, last_update FROM groups ".
      "LEFT JOIN groupvpn on (groups.group_id = groupvpn.group_id) ".
      "WHERE user_id = ".$user->id;
    $db->setQuery($query);
    return $db->loadObjectList("group_id");
  }

  // Request access to a group
  function joinGroup() {
    $groupvpn = $this->loadGroup();
    if(!$groupvpn) {
      return false;
    }

    $user =& JFactory::getUser();
    $db = & JFactory::getDBO();
    $post = JRequest::get("post");
    $secret = GroupVPNModelGroupVPN::getSecret();

    if($groupvpn->detailed_registration) {
      $query = "INSERT INTO groups (user_id, group_id, request, reason, secret,".
        " organization, organizational_unit, country, phone, ethnicity) VALUES ".
        "(".$user->id.", ".$groupvpn->group_id.", 1, \"".$post["reason"]."\", ".
        "\"".$secret."\", \"".$post["organization"]."\", \"".$post["organizational_unit"]."\"".
        ", \"".$post["country"]."\", \"".$post["phone"]."\", \"".$post["ethnicity"]."\")";
    } else {
      $query = "INSERT INTO groups (user_id, group_id, request, reason, secret) VALUES ".
        "(".$user->id.", ".$groupvpn->group_id.", 1, \"".$post["reason"]."\", \"".$secret."\")";
    }

    if($db->Execute($query)) {
      GroupVPNModelGroupVPN::sendAdminNotification($user->email, $user->name,
        $user->username, $groupvpn->group_name, $groupvpn->group_id,
        $post["reason"], $this->getAdminEmail($groupvpn->group_id));
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to join group...'));
    return false;
  }

  // Delete yourself from a group
  function leaveGroup() {
    $groupvpn = $this->loadGroup();
    if(!$groupvpn) {
      return false;
    }

    $user =& JFactory::getUser();
    $db = & JFactory::getDBO();
    $query = "DELETE FROM groups WHERE user_id = ".$user->id." and group_id = ".$groupvpn->group_id;
    if($db->Execute($query)) {
      $query = "SELECT count(user_id) FROM groups WHERE admin = 1 and group_id = ".$groupvpn->group_id;
      $db->setQuery($query);
      if(!$db->loadResult()) {
        $this->groupCleanUp($groupvpn);
      }
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to leave group...'));
    return false;
  }

  // Admin
  function storeConfig() {
    $groupvpn = $this->loadGroup();
    if(!$groupvpn) {
      return false;
    }

    $user =& JFactory::getUser();
    if(!$this->isAdmin($groupvpn->group_id, $user->id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();

    require_once(JPATH_COMPONENT.DS."lib".DS."config_generator.php");
    $node = P2PConfigGenerator::defaultNodeConfigParams();
    $node->XmlRpc = 10000;
    $node->Security = true;
    $p2ppool_id = JRequest::getVar("p2ppool");
    if($p2ppool_id == "No") {
      $remote_tas = JRequest::getVar("nodes");
      $node->RemoteTAs = split("[\r\n]+", $remote_tas);
      $node->Namespace = JRequest::getVar("p2p_namespace");
    } else {
      $node->p2ppool_id = $p2ppool_id;
    }

    $ipop = P2PConfigGenerator::defaultIPOPConfigParams();
    $ipop->Namespace = JRequest::getVar("ip_namespace");
    $secure = JRequest::getVar("secure");
    $ipop->EndToEndSecurity = empty($secure) ? "false" : "true";

    $dhcp = P2PConfigGenerator::defaultDHCPConfigParams();
    $dhcp->Namespace = JRequest::getVar("ip_namespace");
    $dhcp->BaseIP = JRequest::getVar("base_address");
    $dhcp->Netmask = JRequest::getVar("netmask");

    $query = "INSERT INTO groupvpn_config (group_id, ".
      "node_params, ipop_params, dhcp_params) VALUES (".$groupvpn->group_id.
      ", '".serialize($node)."', '".serialize($ipop)."', '".serialize($dhcp).
      "') ON DUPLICATE KEY UPDATE node_params=VALUES(node_params), ".
      "ipop_params=VALUES(ipop_params), dhcp_params=VALUES(dhcp_params)";
    $db->Execute($query);

    $this->loadConfig();
    return $groupvpn->group_id;
  }

  function loadConfig() {
    $groupvpn = $this->loadGroup();
    if(!$groupvpn) {
      return false;
    }

    $db = & JFactory::getDBO();
    $query = "SELECT node_params, ipop_params, dhcp_params FROM ".
      "groupvpn_config WHERE group_id = ".$groupvpn->group_id;
    $db->setQuery($query);

    $res = $db->loadRow();
    if(empty($res)) {
      require_once(JPATH_COMPONENT.DS."lib".DS."config_generator.php");
      $node = P2PConfigGenerator::defaultNodeConfigParams();
      $ipop = P2PConfigGenerator::defaultIPOPConfigParams();
      $dhcp = P2PConfigGenerator::defaultDHCPConfigParams();
      return array($node, $ipop, $dhcp);
    }
    return array(unserialize($res[0]), unserialize($res[1]), unserialize($res[2]));
  }

  function generateXMLConfig($groupvpn = null) {
    if(is_null($groupvpn)) {
      $groupvpn = $this->loadGroup();
    }

    if(is_null($groupvpn)) {
      return false;
    }
    $user =& JFactory::getUser();
    if(!$this->isGroupMember($groupvpn->group_id, $user->id)) {
      JError::raiseError("Not a member of group.");
    }

    list($node, $ipop, $dhcp) = $this->loadConfig();

    if(isset($node->p2ppool_id)) {
      $path = JPATH_SITE.DS."components".DS."com_p2ppool".DS."models".DS."pool.php";
      require_once($path);
      $path = JPATH_ADMINISTRATOR.DS."components".DS."com_p2ppool".DS."tables".DS."p2ppools.php";
      require_once($path);
      $pool_model = new P2PPoolModelPool();
      $pool_model->setPool($node->p2ppool_id);
      $pool = $pool_model->getPool();
      $node->Namespace = $pool->namespace;

      $nodes = $pool_model->getNodes();
      $node->RemoteTAs = array();

      foreach($nodes as $lnode) {
        if($pool->tcpport) {
          $node->RemoteTAs[] = "brunet.tcp://".$lnode.":".$pool->tcpport;
        }
        if($pool->udpport) {
          $node->RemoteTAs[] = "brunet.udp://".$lnode.":".$pool->udpport;
        }
      }
    }

    $node->Security = "true";

    $ipop->GroupVPN = new stdClass();
    $ipop->GroupVPN->ServerURI = "https://".JURI::getInstance()->toString(array("host", "port")).
      "/components/com_groupvpn/mono/GroupVPN.rem";
    $ipop->GroupVPN->Group = $groupvpn->group_name;

    $ipop->GroupVPN->UserName = $user->username;

    $db = & JFactory::getDBO();
    $query = "SELECT secret FROM groups WHERE user_id = ".$user->id." and group_id = ".$groupvpn->group_id;
    $db->setQuery($query);
    $ipop->GroupVPN->Secret = $db->loadResult();
    $ipop->EndToEndSecurity = "true";

    require_once(JPATH_SITE.DS."components".DS."com_groupvpn".DS."lib".DS."config_generator.php");
    $nodeconfig = P2PConfigGenerator::generateNodeConfig($node);
    $ipopconfig = P2PConfigGenerator::generateIPOPConfig($ipop);
    $dhcpconfig = P2PConfigGenerator::generateDHCPConfig($dhcp);
    $node->Security = null;
    $bootstrapconfig = P2PConfigGenerator::generateNodeConfig($node);

    return array($nodeconfig, $ipopconfig, $dhcpconfig, $bootstrapconfig);
  }

  function loadXMLConfig() {
    $file = $this->generateZipFile();
    require_once(JPATH_COMPONENT.DS."lib".DS."utils.php");
    Utils::transferFile($file, 'config.zip');
    JFile::delete($file);
    return true;
  }

  function generateZipFile() {
    $groupvpn = $this->loadGroup();
    if(is_null($groupvpn)) {
      return false;
    }

    list($nodeconfig, $ipopconfig, $dhcpconfig, $bootstrapconfig) = $this->generateXMLConfig($groupvpn);
    jimport('joomla.filesystem.file');
    jimport('joomla.filesystem.folder');

    $name = rand();
    $name = md5($name);
    $config =& JFactory::getConfig();
    $path = $config->getValue('config.tmp_path').DS.$name;

    JFolder::create($path);
    $file = $path.".zip";
    $node_path = $path.DS."node.config";
    $bootstrap_path = $path.DS."bootstrap.config";
    $ipop_path = $path.DS."ipop.config";
    $dhcp_path = $path.DS."dhcp.config";

    JFile::write($node_path, $nodeconfig);
    JFile::write($ipop_path, $ipopconfig);
    JFile::write($dhcp_path, $dhcpconfig);
    JFile::write($bootstrap_path, $bootstrapconfig);
    $cacert_path = JPATH_SITE.DS."components".DS."com_groupvpn".DS."data".DS.$groupvpn->group_name.DS."cacert";
    $webcert_path = JPATH_SITE.DS."components".DS."com_groupvpn".DS."data".DS."webcert";
    exec("zip -jr9 ".$file." ".$node_path." ".$ipop_path." ".$dhcp_path.
      " \"".$cacert_path."\" \"".$webcert_path."\" ".$bootstrap_path);
    JFolder::delete($path);
    return $file;
  }

  function getP2PPools() {
    $path = JPATH_SITE.DS."components".DS."com_p2ppool".DS."models".DS."pool.php";
    jimport('joomla.filesystem.file');
    if(!JFile::exists($path)) {
      return false;
    }
    require_once($path);
    return P2PPoolModelPool::getPools();
  }

  // Grant user membership
  function acceptUser() {
    $groupvpn = $this->loadGroup();
    if(!$groupvpn) {
      return false;
    }

    $user =& JFactory::getUser();
    if(!$this->isAdmin($groupvpn->group_id, $user->id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();
    $query = "UPDATE groups SET request = 0, member = 1 ".
      "WHERE user_id = ".$user->id." and group_id = ".$groupvpn->group_id;
    if($db->Execute($query)) {
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to set membership...'));
    return false;
  }

  // Deny user membership
  function denyUser() {
    $groupvpn = $this->loadGroup();
    if(!$groupvpn) {
      return false;
    }

    $user =& JFactory::getUser();
    if(!$this->isAdmin($groupvpn->group_id, $user->id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();
    $query = "UPDATE groups SET request = 0 ".
      "WHERE user_id = ".$user->id." and group_id = ".$groupvpn->group_id;
    if($db->query($query)) {
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to deny membership...'));
    return false;
  }

  // Grant user admin status
  function promoteUser() {
    $groupvpn = $this->loadGroup();
    if(!$groupvpn) {
      return false;
    }

    $user =& JFactory::getUser();
    if(!$this->isAdmin($groupvpn->group_id, $user->id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();
    $query = "UPDATE groups SET admin = 1 ".
      "WHERE user_id = ".$user->id." and group_id = ".$groupvpn->group_id;
    if($db->query($query)) {
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to promote...'));
    return false;
  }

  function demoteUser() {
    $groupvpn = $this->loadGroup();
    if(!$groupvpn) {
      return false;
    }

    $user =& JFactory::getUser();
    if(!$this->isAdmin($groupvpn->group_id, $user->id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();
    $query = "UPDATE groups SET admin = 0 ".
      "WHERE user_id = ".$user->id." and group_id = ".$groupvpn->group_id;
    if($db->query($query)) {
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to deny membership...'));
    return false;
  }

  function manageGroup() {
    $group_id = JRequest::getVar("group_id");
    $group_name = $this->loadGroup()->group_name;
    if(!$this->isAdmin($group_id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();
    $value = JRequest::getVar("promote");
    if($value) {
      $line = implode($value, " or user_id = ");
      $line = "( user_id = ".$line." )";
      $query = "UPDATE groups SET admin = 1 WHERE group_id = ".$group_id.
        " AND ".$line." AND revoked = 0 AND member = 1";
      $db->Execute($query);
    }

    $value = JRequest::getVar("demote");
    if($value) {
      $line = implode($value, " or user_id = ");
      $line = "( user_id = ".$line." )";
      $query = "UPDATE groups SET admin = 0 WHERE group_id = ".$group_id.
        " AND ".$line." AND revoked = 0 AND member = 1";
      $db->Execute($query);
    }

    $value = JRequest::getVar("accept");
    if($value) {
      foreach($value as $uid) {
        $user =& JFactory::getUser($uid);
        GroupVPNModelGroupVPN::sendUserNotification($user->email, $user->name, $group_name, true);
      }
      $line = implode($value, " or user_id = ");
      $line = "( user_id = ".$line." )";
      $query = "UPDATE groups SET member = 1, request = 0 WHERE group_id = ".
        $group_id." AND ".$line." AND revoked = 0 AND request = 1";
      $db->Execute($query);
    }

    $value = JRequest::getVar("deny");
    if($value) {
      foreach($value as $uid) {
        $user =& JFactory::getUser($uid);
        GroupVPNModelGroupVPN::sendUserNotification($user->email, $user->name, $group_name, false);
      }
      $line = implode($value, " or user_id = ");
      $line = "( user_id = ".$line." )";
      $query = "UPDATE groups SET request = 0 WHERE group_id = ".$group_id.
        " AND ".$line;
      $db->Execute($query);
    }

    $value = JRequest::getVar("revoke");
    if($value) {
      foreach($value as $uid) {
        $user =& JFactory::getUser($uid);
        GroupVPNModelGroupVPN::sendUserNotification($user->email, $user->name, $group_name, true);
      }
      $line = implode($value, ", user_id = ");
      $line = "user_id = ".$line;
      $query = "UPDATE groups SET revoked = 1, admin = 0, member = 0 ".
        "WHERE group_id = ".$group_id." AND ".$line;
      $db->Execute($query);
    }
  }
  
  function deleteGroup() {
    $groupvpn = $this->loadGroup();
    if(!$groupvpn) {
      return false;
    }

    $user =& JFactory::getUser();
    if(strtolower($user->usertype) != "super administrator") {
      if(!$this->isAdmin($groupvpn->group_id, $user->id)) {
        JError::raiseError(403, JText::_('Access Forbidden'));
      }
    }

    return $this->groupCleanUp($groupvpn);
  }

  function groupCleanUp($groupvpn) {
    $db = & JFactory::getDBO();
    $query = "DELETE FROM groups WHERE group_id = ".$groupvpn->group_id;
    $db->Execute($query);
    JFolder::delete(JPATH_COMPONENT.DS."data".DS.$groupvpn->group_name);
    JFolder::delete(JPATH_COMPONENT.DS."private".DS.$groupvpn->group_name);
    $groupvpn->delete();

    return true;
  }

  // Helper functions
  // returns a group table if one exists
  function loadGroup() {
    $groupvpn =& $this->getTable("groupvpn");
    if($groupvpn->load(JRequest::getVar("group_id"))) {
      return $groupvpn;
    }

    JError::raiseWarning(500, JText::_('No such group...'));
    return false;
  }

  function groupAvailable() {
    return JRequest::getVar("group_id");
  }

  // Returns 1 if admin, 0 or false otherwise
  function isAdmin($group_id = null, $user_id = null) {
    if(empty($group_id)) {
      $group_id = JRequest::getVar("group_id");
    }
    if(empty($user_id)) {
      $user =& JFactory::getUser();
      $user_id =  $user->id;
    }

    $db = & JFactory::getDBO();
    $query = "SELECT admin FROM groups WHERE group_id = ".$group_id." and user_id = ".$user_id;
    $db->setQuery($query);
    return $db->loadResult();
  }

  static function getSecret() {
    srand(time());
    $str = "";
    $i = 0;
    while($i < 20) {
      $num = rand(48, 122);
      if($num >= 58 && $num <= 64) {
        continue;
      } else if($num >= 91 && $num <= 96) {
        continue;
      }
      $str .= chr($num);
      $i++;
    }
    return $str;
  }

  static function isGroupMember($group_id = null, $user_id = null) {
    if(empty($group_id)) {
      $group_id = JRequest::getVar("group_id");
    }
    if(empty($user_id)) {
      $user =& JFactory::getUser();
      $user_id =  $user->id;
    }

    $db = & JFactory::getDBO();
    $query = "SELECT member FROM groups WHERE group_id = ".$group_id." and user_id = ".$user_id;
    $db->setQuery($query);
    return $db->loadResult();
  }

  function getUserInfo() {
    $user_id = JRequest::getVar("user_id");
    $group_id = JRequest::getVar("group_id");
    $query = "SELECT t1.*, t2.name, t2.username, t2.email ".
      "FROM groups AS t1 JOIN #__users AS t2 ON t2.id=t1.user_id ".
      "WHERE t1.user_id = ".$user_id." and t1.group_id = ".$group_id;
    $res = $this->_getList($query);
    if(count($res) == 1) {
      $res = $res[0];
    } else {
      $res = null;
    }
    return $res;
  }

  function getAdminEmail($group_id) {
    $db    =& JFactory::getDBO();
    $query = 'SELECT email FROM #__users WHERE id IN '.
      '(SELECT user_id FROM groups WHERE admin = 1 and group_id = '.$group_id.')';
    $db->setQuery($query);
    return $db->loadResultArray();
  }

  static function sendAdminNotification($email, $name, $username, $group, $group_id, $reason, $admin_emails) {
    $config    = &JFactory::getConfig();
    $sitename  = $config->getValue('sitename');

    // Set the e-mail parameters
    $mailfrom    = $config->getValue('mailfrom');
    $fromname  = $config->getValue('fromname');
    $subject  = $group.' Registrant at '.$sitename;
    $body = "A new user has applied for ".$group." access: \n\n";
    $body .= "Name - ".$name."\n";
    $body .= "e-mail - ".$email."\n";
    $body .= "Username - ".$username."\n";
    $body .= "Reason - ".$reason."\n\n";
    $body .= "Please log into the ".$group_name." Group interface to approve or deny access.";

    foreach($admin_emails as $admin_email) {
      JUtility::sendMail($mailfrom, $fromname, $admin_email, $subject, $body);
    }
  }

  static function sendUserNotification($email, $name, $group, $allowed) {
    $config    = &JFactory::getConfig();
    $sitename  = $config->getValue('sitename');

    // Set the e-mail parameters
    $mailfrom    = $config->getValue('mailfrom');
    $fromname  = $config->getValue('fromname');
    $subject  = 'Response to your '.$group.' Group registration at '.$sitename;
    $body = "Dear ".$name.",\n\n";
    $body .= "Your request into ".$group." has been: ";
    if($allowed) {
      $body .= "accepted, you can now download configuration data from the group.";
    } else {
      $body .= "denied.  Please contact one of the group administrators for more";
      $body .= "information";
    }

    JUtility::sendMail($mailfrom, $fromname, $email, $subject, $body);
  }
}
