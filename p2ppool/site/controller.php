<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.controller');

class P2PPoolController extends JController
{
  function allowManagement() {
    $user =& JFactory::getUser();

    if($user->usertype == "Administrator" or
      $user->usertype == "Super Administrator"
    ) {
//      or
//      $user->usertype == "Publisher") {
      $enable = true;
    } else {
      $enable = false;
    }

    return $enable;
  }

  // Run through the model (and database) to see if there is any tasks that
  // need to be run
  function checkTasks() {
    $pool_model =& $this->getModel("Pool");
    $tasks = $pool_model->checkTasks();
    foreach($tasks as $task) {
      if($task == "crawl") {
        $pools = $pool_model->getPools();
        foreach($pools as $pool) {
          if(!$pool_model->setPool($pool[0]) or !$pool_model->pool->running) {
            continue;
          }
          $system =& $this->getModel("System");
          $system->setPool($pool_model->pool);
          $system->runAction($task);
        }
      } else if($task == "check") {
        $pools = $pool_model->getPools();
        foreach($pools as $pool) {
          if(!$pool_model->setPool($pool[0]) or
            !$pool_model->pool->running or
            $pool_model->pool->test)
          {
            continue;
          }
          $system =& $this->getModel("System");
          $system->setPool($pool_model->pool);
          $system->runAction($task);
        }
      } else if($task == "uninstall") {
        list($to_kill, $finished) = $pool_model->removePools();
        $system =& $this->getModel("System");
        foreach($finished as $pool) {
          $system->deleteFiles($pool);
        }
        foreach($to_kill as $pool) {
        }
      }
    }
  }

  function adminAction() {
    if(!$this->allowManagement()) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $pool_model =& $this->getModel("Pool");
    if(!$pool_model->setPool(JRequest::getVar("pool_id"))) {
      $msg = "No pool selected.";
      $link = "index.php?option=com_p2ppool";
      $this->setRedirect($link, $msg);
      return;
    }

    $action = JRequest::getVar("action");
    if(empty($action)) {
      $msg = "No action selected.";
      $link = "index.php?option=com_p2ppool";
      $this->setRedirect($link, $msg);
      return;
    }

    $system =& $this->getModel("System");
    $system->setPool($pool_model->pool);
    $system->runAction($action);
    $msg = "Called ".$action." on ".$pool_model->pool->pool."...";

    $link = "index.php?option=com_p2ppool";
    $this->setRedirect($link, $msg);
  }

  // Allows an admin to force a check on the system
  function check() {
    if(!$this->allowManagement()) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $pool_model =& $this->getModel("Pool");
    if(!$pool_model->setPool(JRequest::getVar("pool_id"))) {
      $link = "index.php?option=com_p2ppool";
      $this->setRedirect($link);
    }

    $system =& $this->getModel("System");
    $system->setPool($pool_model->pool);
    $system->runAction("check");
    $msg = "Checking ".$pool_model->pool->pool."...";

    $link = "index.php?option=com_p2ppool";
    $this->setRedirect($link, $msg);
  }

  // Allows an admin to force a crawl on the system
  function crawl() {
    if(!$this->allowManagement()) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $pool_model =& $this->getModel("Pool");
    if(!$pool_model->setPool(JRequest::getVar("pool_id"))) {
      $link = "index.php?option=com_p2ppool";
      $this->setRedirect($link);
    }

    $system =& $this->getModel("System");
    $system->setPool($pool_model->pool);
    $system->runAction("crawl");
    $msg = "Crawling ".$pool_model->pool->pool."...";

    $link = "index.php?option=com_p2ppool";
    $this->setRedirect($link, $msg);
  }

  // Allows an admin to force a garbage collection  on the system
  function uninstall() {
    if(!$this->allowManagement()) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $model =& $this->getModel("Pool");
    list($to_kill, $finished) = $model->removePools();
    $system =& $this->getModel("System");
    foreach($finished as $pool) {
      print $pool;
      $system->deleteFiles($pool);
    }
    foreach($to_kill as $pool) {
    }
    $msg = "Cleaning systems...";
    $link = "index.php?option=com_p2ppool";
    $this->setRedirect($link, $msg);
  }

  // Process the stopping of the pool by setting uninstall and starting the
  // system uninstall.  If that has been done, let's remove it from the system.
  function stop() {
    if(!$this->allowManagement()) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    $pool =& $this->getModel("Pool");
    $pool->setPool(JRequest::getVar("pool_id"));
    if(empty($pool->pool)) {
      $pool->loadDefaultPool();
    }
    $system =& $this->getModel("System");
    $system->setPool($pool->pool);
    $system->destroySystem();
    $pool->removePool();

    $msg = "Stopping pool...";
    $link = "index.php?option=com_p2ppool";
    $this->setRedirect($link, $msg);
  }

  // Create a new Pool
  function create() {
    $this->update(true);
  }

  function upgrade() {
    $this->update(false);
  }

  function update($create) {
    if(!$this->allowManagement()) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }

    jimport('joomla.filesystem.file');

    $nodes = JRequest::getVar('nodes', null, 'files', 'array');
    if($nodes['tmp_name']) {
      $nodes = JFile::read($nodes['tmp_name']);
    } else {
      $nodes = NULL;
    }

    $pool_model =& $this->getModel("Pool");
    if(!$pool_model->set($nodes)) {
      JError::raiseWarning(500, JText::_('Pool is being uninstalled...'));
      $link = "index.php?option=com_p2ppool";
      $this->setRedirect($link);
      return;
    }

    // at some point we need to make sure we aren't performing
    // overlapping tasks
    $ssh_key = JRequest::getVar('ssh_key', null, 'files', 'array');
    $ssh_key = $ssh_key['tmp_name'];

    $files = JRequest::getVar('files', null, 'files', 'array');
    $files = $files['tmp_name'];

    $system =& $this->getModel("System");
    $system->setPool($pool_model->pool);
    $system->update($ssh_key, $files, $pool_model->getPoolIPs());

    if($create) {
      $msg = "Creating pool...";
    } else {
      $msg = "Updating pool...";
    }

    $link = "index.php?option=com_p2ppool";
    $this->setRedirect($link, $msg);
  }

  function gatherLogs() {
    if(!$this->allowManagement()) {
      JError::raiseError(403, JText::_('Access Forbidden'));
    }
    $link = "index.php?option=com_p2ppool";

    $pool =& $this->getModel("Pool");
    $pool->setPool(JRequest::getVar("pool_id"));
    if(empty($pool->pool)) {
      $pool->setDefaultPool();
    }
    $system =& $this->getModel("System");
    $system->setPool($pool->pool);
    $system->gatherLogs();
    $this->setRedirect($link, "Gathering logs...");
  }

  // View handler ...
  function viewHandler() {
    $view_type = JRequest::getVar("view");
    if(empty($view_type)) {
      $view_type = "status";
    }

    $model =& $this->getModel("Pool");
    $model->setPool(JRequest::getVar("pool_id"));
    if($view_type == "create" or $view_type == "upgrade") {
      if($view_type == "upgrade" and empty($model->pool)) {
        $model->loadDefaultPool();
      }
      $view_type = "edit";
    }
    $view =& $this->getView($view_type);

    $view->setModel($model, $default = true);
    if($view_type == "status") {
      $view->display($management = $this->allowManagement());
    } else {
      $view->display();
    }
  }
}
