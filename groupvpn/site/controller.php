<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.controller');

class GroupVPNController extends JController
{
  function __construct($config = array()) {
    $this->linkbase = "index.php?option=com_groupvpn";
    parent::__construct($config);
  }

  function joinGroup() {
    $model =& $this->getModel("GroupVPN");
    if($model->joinGroup()) {
      $msg = "Group join request successful.";
    }

    $this->setRedirect($this->linkbase, $msg);
  }

  function leaveGroup() {
    $model =& $this->getModel("GroupVPN");
    if($model->leaveGroup()) {
      $msg = "Left group.";
    }

    $this->setRedirect($this->linkbase, $msg);
  }

  function manageGroup() {
    $model =& $this->getModel("GroupVPN");
    $model->manageGroup();

    $link = $this->linkbase."&view=group&group_id=".JRequest::getVar("group_id");
    $this->setRedirect($link, $msg);
  }

  function deleteGroup() {
    $model =& $this->getModel("GroupVPN");
    if($model->deleteGroup()) {
      $msg = "Group deleted.";
    }

    $this->setRedirect($this->linkbase, $msg);
  }

  function reviewAccount() {
    $model =& $this->getModel("GroupVPN");
    if(!$model->isAdmin()) {
      JError::raiseWarning("Not a manager!");
      $this->setRedirect($this->linkbase);
      return;
    }

    $view =& $this->getView("checkAccount");
    $view->setModel($model, $default = true);
    $view->display();
  }

  // Parses the JRequest view variable to render a view
  function viewHandler() {
    $view_type = JRequest::getVar("view");
    $tpl = null;
    if(empty($view_type)) {
      $view_type = "groups";
    }

    $model =& $this->getModel("GroupVPN");
    $view =& $this->getView($view_type);
    $view->setModel($model, $default = true);
    $view->display($tpl);
  }

  function storeConfig() {
    $model =& $this->getModel("GroupVPN");
    $group_id = $model->storeGroup();
    if(!$group_id) {
      echo "HERE";
      exit;
      $this->setRedirect($this->linkbase);
      return;
    }
    JRequest::setVar("group_id", $group_id);
    $model->storeConfig();

    $msg = "Updated group config!";
    $link = $this->linkbase."&view=group&group_id=".$group_id;
    $this->setRedirect($link, $msg);
  }

  function downloadConfig() {
    $model =& $this->getModel("GroupVPN");
    $model->loadXMLConfig();

    $link = JRoute::_($this->linkbase."&view=group&group_id=".JRequest::getVar("group_id"), -1);
    $this->setRedirect($link, $msg);
  }
}
