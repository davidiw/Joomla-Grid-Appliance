<?php
jimport('joomla.application.component.view');

class GroupVPNViewCheckAccount extends JView {
  function display($tpl = null) {
    $model =& $this->getModel();
    $group = $model->loadGroup();
    $this->assignRef('user', $model->getUserInfo());
    $this->assignRef('group_id', $group->group_id);
		parent::display($tpl);
	}
}
?>
