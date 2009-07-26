<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class GroupAppliancesViewGroups extends JView {
  function display($tpl = null) {
    $model =& $this->getModel();
    $this->assignRef('my_groups', $model->loadMyGroupInformation());
    $this->assignRef('groups', $model->loadGroups());
    $this->assignRef('groupvpns', $model->getGroupVPNs());
    parent::display($tpl);
  }
}
