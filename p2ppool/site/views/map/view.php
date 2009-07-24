<?php 
jimport('joomla.application.component.view');

class P2PPoolViewMap extends JView {
  function display($tpl = null) {
    $document =& JFactory::getDocument();
    $document->addScript("http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=".
      "ABQIAAAAelWMCAhcUeyeVqx-UXs1fhRaxnezzsr9cuNmXgitexKzSvi3YRTFJVPcVUeYoXqycUET4i7lwpah-A");

    $model =& $this->getModel();
    $this->assign('description', $model->pool->description);
    $this->assignRef('coordinates', $model->getNodeCoordinates());
    $this->assign('node_count', $model->getNodeCount());
    $this->assign('consistency', $model->getConsistency());
    $this->assign('date', $model->getSnapshotTime());

    parent::display($tpl);
  }
}
