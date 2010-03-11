<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

class P2PPoolModelPool extends JModel {
  var $pool;
  var $count;
  var $consistency;
  var $node_count;
  var $date;

  function setPool($pool_id, $ignore_uninstalled = false) {
    if($pool_id) {
      $this->pool =& $this->getTable("p2ppools");
      $this->pool->load($pool_id);
      if($this->pool->uninstall and !$ignore_uninstalled) {
        JError::raiseWarning(404, JText::_('No pool nor default pool specified'));
        return false;
      }
      return True;
    }
    return false;
  }

  function setPoolByName($pool_name) {
    $db = & JFactory::getDBO();
    $db->setQuery("SELECT pool_id FROM p2ppools WHERE pool = \"".$pool_name."\"");
    $poolid = $db->loadResult();
    if(empty($poolid)) {
      JError::raiseWarning(404, JText::_('No pool nor default pool specified'));
      return false;
    }
    $this->setPool($poolid);
  }

  // Lock the pool so that we can do operations on it
  function lock($name = "inuse") {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    $db = & JFactory::getDBO();
    $db->Execute("LOCK TABLES p2ppools WRITE");

    $this->pool->load();
    $raise = $this->pool->$name;
    if(empty($raise)) {
      $raise = $this->pool->inuse;
    }
    if(!$raise) {
      $this->pool->$name = 1;
      $this->pool->$name;
      $this->pool->store();
    }
    $db->Execute("UNLOCK TABLES");
    if($raise) {
      JError::raiseWarning(500, JText::_('Pool is in use, please wait for the operation to complete.'));
      return false;
    }
    return true;
  }

  // Unlock the pool
  function unlock($name = "inuse") {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    $this->pool->$name = 0;
    $this->pool->store();
  }

  // Add pool to p2p_pools
  // If new:
  // - Create p2p_pool_$pool table for pools node list
  // - Create storage tables for pool, stats, and system_stats
  function set($node_list) {
    $this->pool =& $this->getTable("p2ppools");
    $this->pool->bind(JRequest::get("post"));
    $new = empty($this->pool->pool_id);

    // This could be optimized...
    $pools = $this->getPools();
    foreach($pools as $vals) {
      if($vals[1] == $this->pool->pool) {
        if($vals[0] != $this->pool->pool_id) {
          JError::raiseWarning(500, JText::_('Pool is in use, please wait for the operation to complete.'));
          return false;
        } else {
          break;
        }
      }
    }

    $this->pool->store();

    if(JRequest::getVar("default_pool")) {
      $this->setDefaultPool();
    }

    if($new) {
      $db = & JFactory::getDBO();
      $tables = array("pool.sql", "stats.sql", "system_stats.sql", "count.sql");
      $path = JPATH_COMPONENT.DS."scripts".DS."sql".DS;
      foreach($tables as $table) {
        $data = file_get_contents($path.$table);
        $data = str_replace("#pool_", $this->pool->pool, $data);
        $db->Execute($data);
      }
    }

    if($node_list) {
      $db = & JFactory::getDBO();
      $db->Execute("DELETE FROM ".$this->pool->pool."_pool");
      $nodes = split("[\n\r]+", $node_list);

      foreach($nodes as $node) {
        if(empty($node)) {
          continue;
        }

        $testip = explode(".", $node);
        $isip = true;
        if(count($testip) == 4) {
          foreach($testip as $ipseg) {
            if(ctype_digit($ipseg)) {
            } else {
              $isip = false;
              break;
            }
          }
        } else {
          $isip = false;
        }

        if($isip) {
          $ip = $node;
          $hostname = gethostbyaddr($node);
        } else {
          $ip = gethostbyname($node);
          if(empty($ip) || $ip == $node) {
            continue;
          }
          $hostname = $node;
        }

        $query = "INSERT INTO ".$this->pool->pool."_pool (name, ip, installed) ".
          "VALUES (\"".$hostname."\", \"".$ip."\", 0)";
        $db->Execute($query);
      }
    }
    return true;
  }

  function setDefaultPool() {
    $db = & JFactory::getDBO();
    $db->Execute("UPDATE p2ppools SET default_pool = 0");
    $db->Execute("UPDATE p2ppools SET default_pool = 1 WHERE pool = \"".$this->pool->pool."\"");
  }

  function loadDefaultPool() {
    $db = & JFactory::getDBO();
    $db->setQuery("SELECT pool_id FROM p2ppools WHERE default_pool = 1");
    $poolid = $db->loadResult();
    if(empty($poolid)) {
      JError::raiseWarning(404, JText::_('No pool nor default pool specified'));
    }
    $this->setPool($poolid);
  }

  // Return a list of pools
  static function getPools() {
    $db = & JFactory::getDBO();
    $db->setQuery("SELECT pool_id, pool, description FROM p2ppools");
    $pools = $db->loadRowList();
    return $pools;
  }

  // Returns the pool table object
  function getPool() {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    return $this->pool;
  }

  // Returns the list of IPs where we know BNs are running
  function getPoolIPs() {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    $db = & JFactory::getDBO();
    $db->setQuery("SELECT ip FROM ".$this->pool->pool."_pool");
    return $db->loadResultArray();
  }

  // Sets uninstall and potentially deletes a pool from the database
  function removePool() {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    $poolname = $this->pool->pool;
    $db = & JFactory::getDBO();
    $db->setQuery("SELECT pool FROM p2ppool_taskman WHERE pool = \"".$poolname."\"");

    if(count($db->loadResultArray()) > 0) {
      $this->pool->uninstall = 1;
      $this->pool->running = 0;
      $this->pool->store();
      return;
    }

    $this->pool->delete();
    $tables = array("_pool", "_stats", "_system_stats", "_count");
    foreach($tables as $table) {
      $db->Execute("DROP TABLE ".$poolname.$table);
    }
  }

  // Return the stats for all nodes in a pool, optionally for a specific stat
  // gathering incident specified by count.
  function getNodeStats($count = NULL) {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    if(empty($count)) {
      $this->loadModel();
      $count = $this->count;
    }
    $db = & JFactory::getDBO();

    $query = "SELECT name, #pool_stats.ip, brunet_address, type, consistency,".
      "virtual_ip, namespace, cons, tunnel, udp, tcp  FROM #pool_stats ".
      "LEFT JOIN #pool_pool  ON #pool_stats.ip = ".
      "#pool_pool.ip WHERE #pool_stats.count = ".$count;
    $query = str_replace("#pool", $this->pool->pool, $query);
    $db->setQuery($query);
    $installed = $db->loadAssocList();
    
#    $query = "SELECT name, ip FROM #pool_pool WHERE ip NOT IN (SELECT ip FROM ".
#      "#pool_stats WHERE count = ".$count.")";
#    $query = str_replace("#pool", $this->pool->pool, $query);
#    $db->setQuery($query);
#    $missing = $db->loadRowList();
    
    return array($installed, $missing);
  }

  // Return the coordinates of all Nodes
  function getNodeCoordinates() {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    if(empty($this->count)) {
      $this->loadModel();
    }
    $db = & JFactory::getDBO();
    $query = "SELECT geo_loc FROM #pool_stats WHERE count = ".$this->count." and ".
      "geo_loc != \"0.0000, 0.0000\" and geo_loc != \"\" and geo_loc != \",\"";
    $query = str_replace("#pool", $this->pool->pool, $query);
    $db->setQuery($query);
    return $db->loadResultArray();
  }

  // An accessor for the node count
  function getNodeCount() {
    if(empty($this->node_count)) {
      $this->loadModel();
    }
    return $this->node_count;
  }

  // An accessor for the consistency
  function getConsistency() {
    if(empty($this->consistency)) {
      $this->loadModel();
    }
    return $this->consistency;
  }

  // An accessor for the snapshot time
  function getSnapshotTime() {
    if(empty($this->date)) {
      $this->loadModel();
    }

    return $this->date;
  }

  // Loads the data for a specific pool
  function loadModel() {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    $db = & JFactory::getDBO();
    $query = "SELECT count, date FROM #pool_count WHERE count = ".
      "(SELECT MAX(count) FROM #pool_count)";
    $query = str_replace("#pool", $this->pool->pool, $query);
    $db->setQuery($query);
    list($this->count, $this->date) = $db->loadRow();
    $db->setQuery($query);

    $query = "SELECT nodes, consistency FROM #pool_system_stats WHERE count =".$this->count;
    $query = str_replace("#pool", $this->pool->pool, $query);
    $db->setQuery($query);
    list($this->node_count, $this->consistency) = $db->loadRow();
  }

  // Return the system stats for a specific pool, optionally for a specific
  // stat gathering incident specified by count or range.  If count and range
  // are used together, count refers to the end of the range.  So the result
  // will be all nodes from ($count - $range) to $count.
  function getSystemStats($count = NULL, $range = 20) {
    if(empty($count)) {
      $this->loadModel();
      $count = $this->count;
    }

    $db = & JFactory::getDBO();
    $query = "SELECT date, nodes, consistency FROM #pool_system_stats JOIN ".
      "#pool_count ON #pool_count.count = #pool_system_stats.count ".
      "where #pool_system_stats.count >= ".($count - $range)." ORDER BY ".
      "#pool_count.count DESC";
    $query = str_replace("#pool", $this->pool->pool, $query);
    $db->setQuery($query);
    return $db->loadRowList();
  }

  // Return the stats for all nodes, optionally for a specific stat gathering
  // incident specified by count.  Needs to be ported from com_system from my
  // old code.
  function findBadNodes($start = NULL, $end = NULL) {
  }

  function getOnlineNodes() {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    $db = & JFactory::getDBO();
    $pp = $this->pool->pool;
    $query = "SELECT ip FROM ".$pp."_stats ".
      "WHERE count = (SELECT MAX(count) FROM ".$pp."_count) ".
      "AND ip IN (SELECT ip FROM ".$pp."_pool)";
    $db->setQuery($query);
    return $db->loadResultArray();
  }

  function getNodes() {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    $db = & JFactory::getDBO();
    $query = "SELECT ip FROM ".$this->pool->pool."_pool";
    $db->setQuery($query);
    return $db->loadResultArray();
  }

  // Returns a list of tasks that can be executed
  function checkTasks() {
    $db = & JFactory::getDBO();
    $query = "SELECT task,recurring,period FROM p2ppool_tasks WHERE next_run <= FROM_UNIXTIME(".time().")";
    $db->setQuery($query);
    $res = $db->loadRowList();
    $jobs = Array();
    foreach($res as $val) {
      $jobs[] = $val[0];
      $query = "UPDATE p2ppool_tasks SET next_run = FROM_UNIXTIME(".(time() + $val[2]).
        ") WHERE task = \"".$val[0]."\"";
      $db->Execute($query);
    }
    return $jobs;
  }

  // Checks to see if a pool can be removed
  function removePools() {
    $db = & JFactory::getDBO();
    $query = "SELECT pool, pool_id FROM p2ppools WHERE uninstall = 1";
    $db->setQuery($query);
    $pools = $db->loadRowList();
    $remove = array();
    $kill = array();
    foreach($pools as $pool) {
      $query = "SELECT task FROM p2ppool_taskman WHERE pool = \"" + $pool[0] + "\"";
      $db->setQuery($query);
      $res = $db->loadResult();
      if(empty($res)) {
        $this->setPool($pool[1], true);
        $this->removePool();
        $remove[] = $pool[0];
      } else {
        $kill[] = $pool[0];
      }
    }
    return array($kill, $remove);
  }

  // Information regarding all pools
  function getPoolState() {
    if(empty($this->pool)) {
      $this->loadDefaultPool();
    }

    $db = & JFactory::getDBO();
    $query = "SELECT task, pid, start_time FROM p2ppool_taskman WHERE pool = \"".$this->pool->pool."\"";
    $db->setQuery($query);
    return $db->loadAssocList();
  }
}
?>
