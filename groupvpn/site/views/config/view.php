<?php 
jimport('joomla.application.component.view');

class GroupVPNViewConfig extends JView {
  function display($tpl = null) {
    $model =& $this->getModel();
    $group = $model->loadGroup();
    list($node, $ipop, $dhcp) = $model->loadConfig();
    $this->assignRef('group', $model->loadGroup());
    $this->assignRef('pools', $model->getP2PPools());
    $this->assignRef('node', $node);
    $this->assignRef('ipop', $ipop);
    $this->assignRef('dhcp', $dhcp);
    parent::display($tpl);
  }
}
