<?php
// no direct access
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

class GroupAppliancesModelGroupAppliances extends JModel {
  function __construct($config = array()) {
    $users_db = "groupappliances_users";
    $groups_db = "groupappliances";
    $group_id = "ga_id";
    parent::_construct($config);
  }

  // List of global methods
  // Public
  function loadGroups() {
    $db = & JFactory::getDBO();
    $query = "SELECT ".$this->group_id.", group_name, description FROM ".$this->groups_db;
    $db->setQuery($query);
    return $db->loadObjectList($this->group_id);
  }

  // Admin
  function storeGroup() {
    $group =& $this->getTable($this->groups_db);
    $group->bind(JRequest::get("post"));
    $user =& JFactory::getUser();
    $group_id = $group->$this->group_id;

    if($group->ga_id) {
      if(!$this->isAdmin($group_id, $user->id)) {
        JError::raiseError(403, JText::_('Access Forbidden'));
      }
      $group->store();
    } else {
      if(!$group->store()) {
        JError::raiseWarning(500, JText::_('A similar group already exists...'));
        return false;
      }
      $db = & JFactory::getDBO();
      $query = "INSERT INTO ".$this->users_db." (user_id, ".$this->group_id. ", ".
        "member, admin, reason) VALUES (".$user->id.", ".$group_id.
        ", 1, 1, \"Creator\")";
      $db->Execute($query);
    }

    return $group_id;
  }

  // List of individual methods
  // Public / User
  function loadGroupMembers() {
    $group = $this->loadGroup();
    if(!$group) {
      return false;
    }

    $user =& JFactory::getUser();
    $group_id = $group->$this->group_id;
    if($this->isAdmin($group_id, $user->id)) {
      $query = "SELECT reason, user_id, username, name, email, admin, request, ".
        " member, revoked FROM ".$this->users_db."LEFT JOIN #__users on ".
        "(".$this->users_db.".user_id = #__users.id) ".
        "WHERE ".$this->group_id." = ".$group_id;
    } else {
      $query = "SELECT reason, user_id, username, name, email, admin, ".
        " member, revoked FROM ".$this->users_db."LEFT JOIN #__users on ".
        "(".$this->users_db.".user_id = #__users.id) ".
        "WHERE ".$this->group_id." = ".$group_id;
    }

    $db = & JFactory::getDBO();
    $db->setQuery($query);
    return $db->loadObjectList("user_id");
  }

  function loadMyGroupInformation() {
    $user =& JFactory::getUser();
    $db = & JFactory::getDBO();
    $query = "SELECT ".$this->group_id.", group_id, reason, request, member, ".
      "admin, revoked FROM ".$this->users_db." WHERE user_id = ".$user->id;
    $db->setQuery($query);
    return $db->loadObjectList($this->group_id);
  }

  // Request access to a group
  function joinGroup() {
    $group = $this->loadGroup();
    if(!$group) {
      return false;
    }

    $user =& JFactory::getUser();
    $db = & JFactory::getDBO();
    $reason = JRequest::getVar("reason");
    $query = "INSERT INTO ".$this->users_db. "(user_id, ".$this->group_id.
      ", request, reason) VALUES (".$user->id.", "
      .$group->$this->group_id.", ".$group->group_id.", 1, \"".$reason."\")";
    if($db->Execute($query)) {
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to join group...'));
    return false;
  }

  // Delete yourself from a group
  function leaveGroup() {
    $group = $this->loadGroup();
    if(!$group) {
      return false;
    }

    $user =& JFactory::getUser();
    $db = & JFactory::getDBO();
    $group_id = $group->$this->group_id;
    $query = "DELETE FROM ".$this->users_db." WHERE user_id = ".$user->id.
      " and ".$this->group_id." = ".$group_id;
    if($db->Execute($query)) {
      $query = "SELECT count(user_id) FROM ".$this->users_db." WHERE admin = 1 and ".
        $this->group_id." = ".$group_id;
      $db->setQuery($query);
      if(!$db->loadResult()) {
        $this->groupCleanUp($group);
      }
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to leave group...'));
    return false;
  }

  function getGroupVPNs() {
    $path = JPATH_SITE.DS."components".DS."com_groupvpn".DS."models".DS."groupvpn.php";
    jimport('joomla.filesystem.file');
    if(!JFile::exists($path)) {
      return false;
    }
    require_once($path);
    return GroupVPNModelGroupVPN::loadGroups();
  }

  // Grant user membership
  function acceptUser() {
    $group = $this->loadGroup();
    if(!$group) {
      return false;
    }

    $user =& JFactory::getUser();
    if(!$this->isAdmin($group->group_id, $user->id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();
    $query = "UPDATE ".$this->users_db." SET request = 0, member = 1 ".
      "WHERE user_id = ".$user->id." and ".$this->group_id." = ".
      $group->$this->group_id;
    if($db->Execute($query)) {
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to set membership...'));
    return false;
  }

  // Deny user membership
  function denyUser() {
    $group = $this->loadGroup();
    if(!$group) {
      return false;
    }

    $user =& JFactory::getUser();
    $group_id = $group->$this->group_id;
    if(!$this->isAdmin($group_id, $user->id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();
    $query = "UPDATE ".$this->users_db." SET request = 0 ".
      "WHERE user_id = ".$user->id." and ".$this->group_id." = ".$group_id;
    if($db->query($query)) {
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to deny membership...'));
    return false;
  }

  // Grant user admin status
  function promoteUser() {
    $group = $this->loadGroup();
    if(!$group) {
      return false;
    }

    $user =& JFactory::getUser();
    $group_id = $group->$this->group_id;
    if(!$this->isAdmin($group_id, $user->id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();
    $query = "UPDATE ".$this->users_db." SET admin = 1 ".
      "WHERE user_id = ".$user->id." and ".$this->group_id." = ".$group_id;
    if($db->query($query)) {
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to promote...'));
    return false;
  }

  function demoteUser() {
    $group = $this->loadGroup();
    if(!$group) {
      return false;
    }

    $user =& JFactory::getUser();
    $group_id = $group->$this->group_id;
    if(!$this->isAdmin($group_id, $user->id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();
    $query = "UPDATE ".$this->users_db." SET admin = 0 ".
      "WHERE user_id = ".$user->id." and ".$this->group_id." = ".$group_id;
    if($db->query($query)) {
      return true;
    }

    JError::raiseWarning(500, JText::_('Unable to deny membership...'));
    return false;
  }

  function manageGroup() {
    $group_id = JRequest::getVar($this->group_id);
    if(!$this->isAdmin($group_id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $db = & JFactory::getDBO();
    $value = JRequest::getVar("promote");
    if($value) {
      $line = implode($value, ", user_id = ");
      $line = "user_id = ".$line;
      $query = "UPDATE ".$this->users_db." SET admin = 1 WHERE ".$this->group_id.
        " = ".$group_id." AND ".$line;
      $db->Execute($query);
    }

    $value = JRequest::getVar("demote");
    if($value) {
      $line = implode($value, ", user_id = ");
      $line = "user_id = ".$line;
      $query = "UPDATE ".$this->users_db." SET admin = 0 WHERE ".$this->group_id.
        " = ".$group_id." AND ".$line;
      $db->Execute($query);
    }

    $value = JRequest::getVar("accept");
    if($value) {
      $line = implode($value, ", user_id = ");
      $line = "user_id = ".$line;
      $query = "UPDATE ".$this->users_db." SET member = 1, request = 0 WHERE ".
        $this->group_id." = ".$group_id." AND ".$line;
      $db->Execute($query);
    }

    $value = JRequest::getVar("deny");
    if($value) {
      $line = implode($value, ", user_id = ");
      $line = "user_id = ".$line;
      $query = "UPDATE ".$this->users_db." SET request = 0 WHERE ".$this->group_id.
        " = ".$group_id." AND ".$line;
      $db->Execute($query);
    }

    $value = JRequest::getVar("revoke");
    if($value) {
      $line = implode($value, ", user_id = ");
      $line = "user_id = ".$line;
      $query = "UPDATE ".$this->users_db." SET revoked = 1, admin = 0, member = 0, request = 0".
       " WHERE ".$this->group_id." = ".$group_id." AND ".$line;
      $db->Execute($query);
    }
  }
  
  function deleteGroup() {
    $group = $this->loadGroup();
    if(!$group) {
      return false;
    }

    $user =& JFactory::getUser();
    if(!$this->isAdmin($group->$this->group_id, $user->id)) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    return $this->groupCleanUp($group);
  }

  function groupCleanUp($group) {
    $db = & JFactory::getDBO();
    $query = "DELETE FROM ".$this->users_db." WHERE ".$this->group_id." = ".
      $group->$this->group_id;
    $db->Execute($query);
    $group->delete();

    return true;
  }

  // Helper functions
  // returns a group table if one exists
  function loadGroup() {
    $group =& $this->getTable($this->groups_db);
    if($group->load(JRequest::getVar($this->group_id))) {
      return $group;
    }

    JError::raiseWarning(500, JText::_('No such group...'));
    return false;
  }

  // Returns 1 if admin, 0 or false otherwise
  function isAdmin($group_id = null, $user_id = null) {
    if(empty($group_id)) {
      $group_id = JRequest::getVar($this->group_id);
    }
    if(empty($user_id)) {
      $user =& JFactory::getUser();
      $user_id =  $user->id;
    }

    $db = & JFactory::getDBO();
    $query = "SELECT admin FROM ".$this->users_db." WHERE ".$this->group_id.
      " = ".$group_id." and user_id = ".$user_id;
    $db->setQuery($query);
    return $db->loadResult();
  }
}
