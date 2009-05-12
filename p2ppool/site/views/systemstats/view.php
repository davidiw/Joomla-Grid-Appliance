<?php 
jimport('joomla.application.component.view');

class P2PPoolViewSystemStats extends JView {
  function display($tpl = null) {
    $document =& JFactory::getDocument();

    $model =& $this->getModel();
    $this->assignRef('stats', $model->getSystemStats());
    parent::display($tpl);
  }
}
