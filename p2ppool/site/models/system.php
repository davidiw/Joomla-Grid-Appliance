<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

class P2PPoolModelSystem extends JModel {
  var $pool = NULL;

  function __construct($config = array()) {
    $this->app = "python ".JPATH_COMPONENT.DS."scripts".DS."python".DS."JoomlaInterface.py";
    parent::__construct($config);
  }

  function setPool($pool) {
    $this->pool = $pool;
  }

  // This is probably horribly worded and should be called beginUninstall
  function destroySystem() {
    $this->available();
    $this->runAction("uninstall");
  }

  // This is called once there are no more processes running, the could
  // potentially be placed into the python code, but I am not sure the best
  // location for it
  static function deleteFiles($pool) {
    $path = JPATH_COMPONENT.DS."data".DS.$pool;
    system("rm -rf ".$path);
    $path = JPATH_COMPONENT.DS."private".DS.$pool;
    system("rm -rf ".$path);
  }

  // Not implemented
  function suspendSystem() {
    $this->available();
  }

  // Did we load a pool?
  function available() {
    if(empty($this->pool)) {
      JError::raiseError(404, JText::_('No pool nor default pool specified'));
    }
  }

  // Execute a task
  function runAction($action) {
    $path = JPATH_COMPONENT.DS."data".DS.$this->pool->pool;
    $task = $this->app." ".$action." ".$this->pool->pool." True &> ".  $path.DS."setup.log";
    system($task);
  }

  // Update our files and optionally run an update on the pool
  function update($ssh_key, $files, $nodes) {
    jimport('joomla.filesystem.file');
    jimport('joomla.filesystem.folder');

    $path = JPATH_COMPONENT.DS."private".DS.$this->pool->pool;
    JFolder::create($path);
    if($ssh_key) {
      JFile::upload($ssh_key, $path.DS."ssh_key");
      chmod($path.DS."ssh_key", 0600);
    }

    $files_update = false;
    $path = JPATH_COMPONENT.DS."data".DS.$this->pool->pool;
    JFolder::create($path);
    if($files) {
      JFile::upload($files, $path.DS."files.zip");
      $files_update = true;
    }

    JFile::write($path.DS.$this->pool->pool.".config", $this->generateConfig($nodes, false));
    JFile::write($path.DS."node.config", $this->generateConfig($nodes));

    $this->pool->running = 1;
    $this->pool->store();
    if($files_update) {
      $this->runAction("install");
    } else {
      $this->runAction("check");
    }
  }

  // Configuration generation  (see what I did there comment = transpose of the
  // combined function name.
  function generateConfig($nodes, $enable_rpc = true) {
    $output[] = "<NodeConfig>";
    $output[] = "  <BrunetNamespace>".$this->pool->namespace."</BrunetNamespace>";
    $output[] = "  <RemoteTAs>";
    if($this->pool->tcpport) {
      foreach($nodes as $node) {
        $output[] = "    <Transport>brunet.tcp://".$node.":".$this->pool->tcpport."</Transport>";
      }
    }
    if($this->pool->udpport) {
      foreach($nodes as $node) {
        $output[] = "    <Transport>brunet.udp://".$node.":".$this->pool->udpport."</Transport>";
      }
    }
    $output[] = "  </RemoteTAs>";
    $output[] = "  <EdgeListeners>";
    if($this->pool->udpport) {
      $output[] = "    <EdgeListener type=\"udp\">";
      $output[] = "      <port>".$this->pool->udpport."</port>";
      $output[] = "    </EdgeListener>";
    }
    if($this->pool->tcpport) {
      $output[] = "    <EdgeListener type=\"tcp\">";
      $output[] = "      <port>".$this->pool->tcpport."</port>";
      $output[] = "    </EdgeListener>";
    }
    $output[] = "  </EdgeListeners>";
    if($enable_rpc) {
      $output[] = "  <XmlRpcManager>";
      $output[] = "    <Enabled>true</Enabled>";
      $output[] = "    <Port>".$this->pool->rpcport."</Port>";
      $output[] = "  </XmlRpcManager>";
    }

    $output[] = "  <NCService>";
    $output[] = "    <Enabled>true</Enabled>";
    $output[] = "    <OptimizeShortcuts>true</OptimizeShortcuts>";
    $output[] = "    <Checkpointing>true</Checkpointing>";
    $output[] = "  </NCService>";

    $output[] = "</NodeConfig>";
    return implode("\n", $output);
  }
}
