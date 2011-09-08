<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

class TableGroupVPN extends JTable {
  var $group_id = null;
  var $group_name = null;
  var $create_time = null;
  var $last_update = null;
  var $description = null;
  var $detailed_registration = 0;
  var $tos = null;

  function TableGroupVPN(&$db) {
    parent::__construct('groupvpn', 'group_id', $db);
  }

  function bind($from, $ignore = array()) {
    if(!parent::bind($from, $ignore)) {
      return false;
    }

    if(is_null($this->group_name) && !is_null($this->group_id)) {
      $db = & JFactory::getDBO();
      $query = "SELECT group_name FROM groupvpn WHERE group_id = ".$this->group_id;
      $db->setQuery($query);
      $this->group_name = $db->loadResult();
    }

    if(strlen($this->group_name) < 3) {
      JError::raiseError(500, JText::_('Invalid group name, must be 3 or more characters'));
    } elseif(0 < preg_match("/[^a-zA-Z0-9_\-\ ]/", $this->group_name)) {
      JError::raiseError(500, JText::_('Invalid group name, must be a-z, A-Z, 0-9, _, -, " "'));
    }
    return true;
  }

  function store($updateNulls = false) {
    if(!parent::store($updateNulls)) {
      return false;
    }

    if(is_null($this->group_id)) {
      $db = & JFactory::getDBO();
      $query = "SELECT group_id FROM groupvpn WHERE group_name = \"".$this->group_name."\"";
      $db->setQuery($query);
      $this->group_id = $db->loadResult();
    }
    return true;
  }
}
