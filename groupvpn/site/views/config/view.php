<?php 
jimport('joomla.application.component.view');

class GroupVPNViewConfig extends JView {
  function display($tpl = null) {
    $model =& $this->getModel();
    $this->assignRef('pools', $model->getP2PPools());
    if($model->groupAvailable()) {
      $group = $model->loadGroup();
      list($node, $ipop, $dhcp) = $model->loadConfig();
      $this->assignRef('group', $model->loadGroup());
      $this->assignRef('node', $node);
      $this->assignRef('ipop', $ipop);
      $this->assignRef('dhcp', $dhcp);
    }
    parent::display($tpl);
  }
}
