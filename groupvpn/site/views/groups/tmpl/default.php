<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
?>

<script type="text/javascript">
function addGroup(group) {
  var form = document.getElementById("form");

  var group_ele = document.createElement("input");
  group_ele.type = "hidden";
  group_ele.name = "group_id";
  group_ele.value = group;

  form.appendChild(group_ele);

  form.submit();
}
</script>

<form action="index.php" method="post" id="form">
<input type="hidden" name="option" value="com_groupvpn" />

<?php if($this->groups) { ?>
<table border=1>
  <tr>
    <td>Group</td>
    <td>Description</td>
    <td>State</td>
    <td>Action</td>
  </tr>
<?php
if($this->my_groups) {
  foreach($this->my_groups as $group) {
?>
  <tr>
    <td>
      <a href="index.php?option=com_groupvpn&task=viewHandler&view=group&group_id=<?php echo $group->group_id; ?>"><?php
        echo $this->groups[$group->group_id]->group_name; ?></a>
    </td>
    <td><?php echo $this->groups[$group->group_id]->description; ?></td>
    <td><?php
$noleave = false;
if($group->admin) {
  echo "admin";
} else if($group->member) {
  echo "member";
} else if($group->request) {
  echo "request";
} else if($group->revoked) {
  $noleave = true;
  echo "revoked";
} else {
  echo "denied";
} ?></td>
    <td><?php
if(!$noleave) {?>
    <button type="submit" name="task" value="leave" onclick="addGroup(<?php echo $group->group_id; ?>)">
      Leave
    </button>
<?php } ?></td>
  </tr>
<?php
  }
}

foreach($this->groups as $group) {
  if($this->my_groups[$group->group_id]) {
    continue;
  }
?>
  <tr>
    <td>
      <a href="index.php?option=com_groupvpn&task=viewHandler&view=group&group_id=<?php echo $group->group_id; ?>"><?php
        echo $group->group_name; ?></a>
    </td>
    <td><?php echo $group->description; ?></td>
    <td />
    <td>
      <button type="submit" name="view" value="account" onclick="addGroup(<?php echo $group->group_id; ?>)">
        Join
      </button>
    </td>
  </tr>
<?php } ?>
</table>
<?php } ?>

<button type="submit" name="view" value="config">Create a new group</button>
</form>
