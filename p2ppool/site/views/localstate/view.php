<?php 
jimport('joomla.application.component.view');

class P2PPoolViewLocalState extends JView {
  function display($tpl = null) {
    $document =& JFactory::getDocument();

    $model =& $this->getModel();
    $model->loadModel();
    $this->assign('pool', $model->pool->pool);
    $this->assign('running', $model->pool->running);
    $this->assign('uninstall', $model->pool->uninstall);
    $this->assignRef('state', $model->getPoolState());
    parent::display($tpl);
  }
}
