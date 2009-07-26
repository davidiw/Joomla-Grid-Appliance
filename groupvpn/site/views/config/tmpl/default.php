<?php // no direct access
defined('_JEXEC') or die('Restricted access');
?>

<script type="text/javascript">
var pool_info_children = new Array();
function SetP2PPool() {
  var p2ppool = document.getElementById("p2ppool");
  if(p2ppool.options[p2ppool.selectedIndex].value != "No") {
    var pool_info = document.getElementById("pool_info");
    var length = pool_info.childNodes.length;
    for(i = 0; i < length; i++) {
      pool_info_children[i] = pool_info.childNodes[0];
      pool_info.removeChild(pool_info.childNodes[0]);
    }
  } else {
    if(pool_info_children) {
      var pool_info = document.getElementById("pool_info");
      for(i = 0; i < pool_info_children.length; i++) {
        pool_info.appendChild(pool_info_children[i]);
      }
      pool_info_children = new Array();
    }
  }
}
</script>

<form action="index2.php" method="post" id="form">
  <table>
    <tbody>
      <tr>
        <td>Group name:</td>
        <td>
<?php if($this->group->group_id) {
  echo $this->group->group_name;
} else {
?>
          <input type="text" name="group_name"/>
<?php } ?>
        </td>
      </tr>
      <tr>
        <td>Group description:</td>
        <td><textarea cols="50" rows="2" name="description"><?php echo $this->group->description; ?></textarea></td>
      </tr>
      <tr>
        <td>Require detailed registration:</td>
        <td><input type="checkbox" name="detailed_registration" value="1" <?php
          if($this->group->detailed_registration) { ?>checked=""<?php } ?>/></td>
      </tr>
      <tr>
        <td>Terms of service (empty implies no terms of service):</td>
        <td><textarea cols="50" rows="10" name="tos"><?php echo $this->group->tos; ?></textarea></td>
      </tr>
    </tbody>
<?php if($this->pools) { ?>
    <tbody>
      <tr>
        <td>Use a Managed P2PPool: </td>
        <td>
          <select name="p2ppool" id="p2ppool" onclick="SetP2PPool()">
            <option value="No" <?php if(empty($this->node->p2ppool_id)) { ?> selected="selected" <?php } ?>>No</option>
<?php foreach($this->pools as $pool) { ?>
            <option value="<?php echo $pool[0]; ?>" <?php if($this->node->p2ppool_id == $pool[0]) { ?> selected="selected" <?php } ?>><?php echo $pool[1]; ?></option>
<?php } ?>
          </select>
        </td>
      </tr>
    </tbody>
<?php } ?>
    <tbody id="pool_info">
      <tr>
        <td>P2P Namespace:</td>
        <td><input type="text" name="p2p_namespace" value="<?php echo $this->node->Namespace; ?>" /></td>
      </tr>
      <tr>
        <td>Line delimited list of RemoteTAs:</td>
        <td><textarea cols="50" rows="10" name="nodes"><?php if($this->node) echo implode($this->node->RemoteTAs, "\n"); ?></textarea></td>
      </tr>
    </tbody>
    <tbody>
      <tr>
        <td>IP Namespace:</td>
        <td><input type="text" name="ip_namespace" value="<?php echo $this->dhcp->Namespace; ?>" /></td>
      </tr>
      <tr>
        <td>Base IP address:</td>
        <td><input type="text" name="base_address" value="<?php echo $this->dhcp->BaseIP; ?>" /></td>
      </tr>
      <tr>
        <td>IP Netmask:</td>
        <td><input type="text" name="netmask" value="<?php echo $this->dhcp->Netmask; ?>" /></td>
      </tr>
      <tr>
        <td>End to End Security:</td>
        <td><input type="checkbox" name="secure" value="true" <?php
          if($this->ipop->EndToEndSecurity == "true") { ?>checked=""<?php } ?> /></td>
      </tr>
    </tbody>
  </table>
  <input type="hidden" name="task" value="storeConfig" />
  <input type="hidden" name="option" value="com_groupvpn" />
  <input type="hidden" name="group_id" value="<?php echo $this->group->group_id; ?>" />
  <input type="submit" value="Submit" />
</form>

<script type="text/javascript">
SetP2PPool();
</script>
