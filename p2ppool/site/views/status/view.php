<?php 
jimport('joomla.application.component.view');

class P2PPoolViewStatus extends JView {
  function display($management, $tpl = null) {
    $this->assignRef('management', $management);
    $model =& $this->getModel();
    $this->assignRef('pools', $model->getPools());
    parent::display($tpl);
  }
}
