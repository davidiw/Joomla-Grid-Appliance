<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

class TableGroupVPN extends JTable {
  var $group_id = null;
  var $group_name = null;
  var $create_time = null;
  var $last_update = null;
  var $description = null;

  function TableGroupVPN(&$db) {
    parent::__construct('groupvpn', 'group_id', $db);
  }

  function store($updateNulls = false) {
    if(!parent::store($updateNulls)) {
      return false;
    }

    $db = & JFactory::getDBO();
    $query = "SELECT group_id FROM groupvpn WHERE group_name = \"".$this->group_name."\"";
    $db->setQuery($query);
    $this->group_id = $db->loadResult();
    return true;
  }
}
