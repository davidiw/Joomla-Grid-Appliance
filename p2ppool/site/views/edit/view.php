<?php 
jimport('joomla.application.component.view');

class P2PPoolViewEdit extends JView {
  function display($tpl = null) {
    $model =& $this->getModel();
    if($model->pool) {
      $this->assignRef('pool', $model->getPool());
      $this->assign('task', "upgrade");
    } else {
      $this->assign('task', "create");
    }
    parent::display($tpl);
  }
}
