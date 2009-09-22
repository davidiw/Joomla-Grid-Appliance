<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class GroupVPNViewGroups extends JView {
  function display($tpl = null) {
    $document =& JFactory::getDocument();
    $document->addScript("includes/js/mootools.js");
    $document->addScript("components/com_p2ppool/scripts/javascript/sorttable.js");

    $model =& $this->getModel();
    $my_groups = $model->loadMyGroupInformation();
    $groups = $model->loadGroups();
    $this->assignRef('my_groups', $my_groups);
    $this->assignRef('groups', $groups);
    $user =& JFactory::getUser();
    $this->assign('admin', strtolower($user->usertype) == "super administrator");
    parent::display($tpl);
  }
}
