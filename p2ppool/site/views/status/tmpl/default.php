<?php // no direct access
defined('_JEXEC') or die('Restricted access');
?>

<script type="text/javascript">
function check() {
  var form = document.getElementById("form");

  var actions = document.getElementsByName("action");
  for(i = 0; i < actions.length; i++) {
    var action = actions[i];
    if(action.checked) {
      var ele = document.createElement("input");
      ele.type = "hidden";
      if(action.value == "logs" || action.value == "stop") {
        ele.name = "task";
      } else {
        ele.name = "view";
      }
      ele.value = action.value;
      form.appendChild(ele);
      break;
    }
  }

  var pools = document.getElementsByName("pool");
  for(i = 0; i < pools.length; i++) {
    var pool = pools[i];
    if(pool.checked) {
      var ele = document.createElement("input");
      ele.type = "hidden";
      ele.name = "pool_id";
      ele.value = pool.value;
      form.appendChild(ele);
      break;
    }
  }
  form.submit();
}

function selectPool() {
}
</script>

<table><tr>
  <td style="vertical-align:top"><table frame="border">
    <tr><td style="horizontal-align:center">Pools</td></tr>
<?php foreach($this->pools as $pool) { ?>
    <tr>
      <td><input type="radio" name="pool" value="<?php echo $pool[0]; ?>" /></td>
      <td><?php echo $pool[1]; ?></td>
      <td><?php echo $pool[2]; ?></td>
    <tr>
<? } ?>
  </table></td>
  <td style="vertical-align:top"><table frame="border">
    <tr><td style="horizontal-align:center">User Tasks</td></tr>
    <tr>
      <td><input type="radio" name="action" value="nodestats" /></td>
      <td>Node Stats</td>
    <tr>
    <tr>
      <td><input type="radio" name="action" value="systemstats" /></td>
      <td>System Stats</td>
    <tr>
    <tr>
      <td><input type="radio" name="action" value="map" /></td>
      <td>Map</td>
    <tr>
<?php if($this->management) { ?>
    <tr><td style="horizontal-align:center">Management Tasks</td></tr>
    <tr>
      <td><input type="radio" name="action" value="create" /></td>
      <td>Create</td>
    <tr>
    <tr>
      <td><input type="radio" name="action" value="stop" /></td>
      <td>Stop</td>
    <tr>
    <tr>
      <td><input type="radio" name="action" value="upgrade" /></td>
      <td>Upgrade</td>
    <tr>
    <tr>
      <td><input type="radio" name="action" value="logs" /></td>
      <td>Gather Logs</td>
    <tr>
<?php } ?>
  </table></td>
</tr></table>

<form action="index.php" method="get" id="form">
  <input type="hidden" name="option" value="com_p2ppool" />
  <input type="button" value="Submit" onclick="check()" />
</form>
