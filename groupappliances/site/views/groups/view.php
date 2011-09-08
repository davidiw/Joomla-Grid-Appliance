<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class GroupAppliancesViewGroups extends JView {
  function display($tpl = null) {
    $document =& JFactory::getDocument();
    $document->addScript("media/system/js/mootools.js");
    $document->addScript("components/com_p2ppool/scripts/javascript/sorttable.js");

    $model =& $this->getModel();
    $this->assignRef('my_groups', $model->loadMyGroupInformation());
    $this->assignRef('groups', $model->loadGroups());
    $this->assignRef('groupvpns', $model->getGroupVPNs());
    parent::display($tpl);
  }
}
