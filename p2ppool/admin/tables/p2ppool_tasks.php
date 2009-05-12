<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

class TableP2PPool_Tasks extends JTable {
  var $task_id = NULL;
  var $task_name = NULL;
  var $recurring = NULL,
  var $next_run = NULL;
  var $period = NULL;

  function TableP2PPool_Tasks(&$db) {
    parent::__construct('p2ppool_tasks', 'task_id', $db);
  }
}
