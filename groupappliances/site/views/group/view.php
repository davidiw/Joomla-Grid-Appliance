<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class GroupAppliancesViewGroup extends JView {
  function display($tpl = null) {
    $document =& JFactory::getDocument();
    $document->addScript("includes/js/mootools.js");
    $document->addScript("components/com_p2ppool/scripts/javascript/sorttable.js");

    $model =& $this->getModel();
    $members = $model->loadGroupMembers();
    $group = $model->loadGroup();
    $this->assignRef('members', $members);
    $this->assignRef('group', $group);
    $this->assignRef('member', $model->isGroupMember());
    $this->assign('admin', $model->isAdmin());
    parent::display($tpl);
  }
}
