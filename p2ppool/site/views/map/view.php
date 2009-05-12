<?php 
jimport('joomla.application.component.view');

class P2PPoolViewMap extends JView {
  function display($tpl = null) {
    $document =& JFactory::getDocument();
    $document->addScript("http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=ABQIAAAAelWMCAhcUeyeVqx-UXs1fhRi_j0U6kJrkFvY4-OX2XYmEAa76BRm_ZkPKJxyHRRCbRTiO2NlheUSxQ");

    $model =& $this->getModel();
    $this->assign('description', $model->pool->description);
    $this->assignRef('coordinates', $model->getNodeCoordinates());
    $this->assign('node_count', $model->getNodeCount());
    $this->assign('consistency', $model->getConsistency());
    $this->assign('date', $model->getSnapshotTime());

    parent::display($tpl);
  }
}
