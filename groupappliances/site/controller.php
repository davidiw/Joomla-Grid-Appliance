<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.controller');

class GroupAppliancesController extends JController
{
  function __construct($config = array()) {
    $this->linkbase = "index.php?option=com_groupappliances";
    parent::__construct($config);
  }

  function joinGroup() {
    $model =& $this->getModel("GroupAppliances");
    if($model->joinGroup()) {
      $msg = "Group join request successful.";
    }

    $this->setRedirect($this->linkbase, $msg);
  }

  function leaveGroup() {
    $model =& $this->getModel("GroupAppliances");
    if($model->leaveGroup()) {
      $msg = "Left group.";
    }

    $this->setRedirect($this->linkbase, $msg);
  }

  function manageGroup() {
    $model =& $this->getModel("GroupAppliances");
    $model->manageGroup();

    $link = $this->linkbase."&view=group&group_id=".JRequest::getVar("group_id");
    $this->setRedirect($link, $msg);
  }

  function deleteGroup() {
    $model =& $this->getModel("GroupAppliances");
    if($model->deleteGroup()) {
      $msg = "Group deleted.";
    }

    $this->setRedirect($this->linkbase, $msg);
  }

  function createGroup() {
    $model =& $this->getModel("GroupAppliances");
    $group_id = $model->storeGroup();
    if($group_id) {
      $msg = "Group created.";
    }

    $link = $this->linkbase."&view=config&group_id=".$group_id;
    $this->setRedirect($link, $msg);
  }

  // Parses the JRequest view variable to render a view
  function viewHandler() {
    $view_type = JRequest::getVar("view");
    if(empty($view_type)) {
      $view_type = "groups";
    }

    $model =& $this->getModel("GroupAppliances");
    $view =& $this->getView($view_type);
    $view->setModel($model, $default = true);
    $view->display();
  }

  function downloadFloppy() {
    $model =& $this->getModel("GroupAppliances");
//    $model->loadXMLConfig();

//    $link = JRoute::_($this->linkbase."&view=group&group_id=".JRequest::getVar("group_id"), -1);
//    $this->setRedirect($link, $msg);
  }
}
