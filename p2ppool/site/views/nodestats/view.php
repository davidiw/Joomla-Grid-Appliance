<?php 
jimport('joomla.application.component.view');

class P2PPoolViewNodeStats extends JView {
  function display($tpl = null) {
    $document =& JFactory::getDocument();
    $document->addScript("media/system/js/mootools.js");
    $document->addScript("components/com_p2ppool/scripts/javascript/sorttable.js");

    $this->assignRef('management', $management);
    $model =& $this->getModel();
    list($nodes, $missing) = $model->getNodeStats();
    $this->assignRef('nodes', $nodes);
    parent::display($tpl);
  }
}
