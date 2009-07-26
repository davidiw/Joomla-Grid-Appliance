<?php
jimport('joomla.application.component.view');

class GroupVPNViewAccount extends JView {
  function display($tpl = null) {
    $user =& JFactory::getUser();
    $model =& $this->getModel();
    $group = $model->loadGroup();
    $this->assignRef('user', $user );
    $this->assignRef('group', $group);
		parent::display($tpl);
	}
}
?>
