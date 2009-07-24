<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

class TableP2PPools extends JTable {
  var $pool_id = null;
  var $pool = null;
  var $user_name = null;
  var $install_path = null;
  var $tcpport = null;
  var $udpport = null;
  var $default_pool = null;
  var $description = null;
  var $rpcport = null;
  var $namespace = null;
  var $mkbundle = null;
  var $running = null;
  var $inuse = null;
  var $uninstall = null;
  var $test = null;

  function TableP2PPools(&$db) {
    parent::__construct('p2ppools', 'pool_id', $db);
  }
}
