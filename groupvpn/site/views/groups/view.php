<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class GroupVPNViewGroups extends JView {
  function display($tpl = null) {
    $model =& $this->getModel();
    $my_groups = $model->loadMyGroupInformation();
    $groups = $model->loadGroups();
    $this->assignRef('my_groups', $my_groups);
    $this->assignRef('groups', $groups);
    parent::display($tpl);
  }
}
