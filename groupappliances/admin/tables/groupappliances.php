<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

class TableGroupAppliances extends JTable {
  var $group_id = null;
  var $ga_id = null;
  var $group_name = null;
  var $create_time = null;
  var $last_update = null;
  var $description = null;

  function TableGroupAppliances(&$db) {
    parent::__construct('groupappliances', 'group_id', $db);
  }

  function store($updateNulls = false) {
    if(!parent::store($updateNulls)) {
      return false;
    }

    $db = & JFactory::getDBO();
    $query = "SELECT ga_id FROM groupappliances WHERE ".
      "group_name = \"".$this->group_name."\" and ".
      "group_id = ".$this->group_id;
    $db->setQuery($query);
    $this->group_id = $db->loadResult();
    return true;
  }
}
