<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class GroupVPNViewGroup extends JView {
  function display($tpl = null) {
    $model =& $this->getModel();
    $members = $model->loadGroupMembers();
    $group = $model->loadGroup();
    $this->assignRef('members', $members);
    $this->assignRef('group', $group);
    $this->assignRef('member', GroupVPNModelGroupVPN::isGroupMember($group->group_id));
    $this->assign('admin', $model->isAdmin());
    parent::display($tpl);
  }
}
