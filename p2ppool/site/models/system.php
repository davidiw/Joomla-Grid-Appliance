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

  function destroySystem() {
    $this->available();
    $this->runAction("uninstall");
  }

  static function deleteFiles($pool) {
    $path = JPATH_COMPONENT.DS."data".DS.$pool;
    system("rm -rf ".$path);
    $path = JPATH_COMPONENT.DS."private".DS.$pool;
    system("rm -rf ".$path);
  }

  function gatherLogs() {
    $this->available();
    $this->runAction("get_logs");
  }

  function suspendSystem() {
    $this->available();
  }

  function checkSystem() {
    $this->runAction("check");
  }

  function crawlSystem() {
    $this->runAction("crawl");
  }

  function available() {
    if(empty($this->pool)) {
      JError::raiseError(404, JText::_('No pool nor default pool specified'));
    }
  }

  function runAction($action) {
    $path = JPATH_COMPONENT.DS."data".DS.$this->pool->pool;
    $task = $this->app." ".$action." ".$this->pool->pool." True &> ".  $path.DS."setup.log";
    system($task);
  }

  function update($ssh_key, $files, $nodes) {
    jimport('joomla.filesystem.file');
    jimport('joomla.filesystem.folder');

    $path = JPATH_COMPONENT.DS."private".DS.$this->pool->pool;
    JFolder::create($path);
    if($ssh_key) {
      JFile::upload($ssh_key, $path.DS."ssh_key");
      chmod($path.DS."ssh_key", 0600);
    }
    $path = JPATH_COMPONENT.DS."data".DS.$this->pool->pool;
    JFolder::create($path);
    if($files) {
      JFile::upload($files, $path.DS."files.zip");
    }

    JFile::write($path.DS.$this->pool->pool.".config", $this->generateConfig($nodes, false));
    JFile::write($path.DS."node.config", $this->generateConfig($nodes));

    $mkbundle = "false";
    if($this->pool->mkbundle) {
      $mkbundle = "true";
    }

    $this->pool->running = 1;
    $this->pool->store();
    $this->runAction("install");
  }

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
    $output[] = "</NodeConfig>";
    return implode("\n", $output);
  }
}
