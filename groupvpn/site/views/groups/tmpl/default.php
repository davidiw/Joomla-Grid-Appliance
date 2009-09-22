<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
?>

<script type="text/javascript">
function addGroup(group, task) {
  var form = document.getElementById("form");

  var group_ele = document.createElement("input");
  group_ele.type = "hidden";
  group_ele.name = "group_id";
  group_ele.value = group;

  form.appendChild(group_ele);

  var task_ele = document.createElement("input");
  task_ele.type = "hidden";
  task_ele.value = task;

  if(task == "account") {
    task_ele.name = "view";
  } else {
    task_ele.name = "task";
  }

  form.appendChild(task_ele);

  form.submit();
}
</script>

<style type="text/css">
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: default;
}
</style>

<form action="index.php" method="post" id="form">
<input type="hidden" name="option" value="com_groupvpn" />

<?php if($this->groups) { ?>
<table border=1 class="sortable">
  <tr>
    <th>Group</th>
    <th>Description</th>
    <th>State</th>
    <th>Action</th>
<?php if($this->admin) { ?>
    <th>Admin Delete</th>
    <th>Last Activity</th>
<?php } ?>
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
    <input type="button" value="Leave" onclick="addGroup(<?php echo $group->group_id; ?>, 'leave')"/>
<?php } ?></td>
<?php if($this->admin) { ?>
    <td>
      <input type="button" value="Delete" onclick="addGroup(<?php echo $group->group_id; ?>, 'delete')"/>
    </td>
    <td><?php echo $group->last_update; ?></td>
<?php } ?>
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
      <input type="button" value="Join" onclick="addGroup(<?php echo $group->group_id; ?>, 'account')" />
    </td>
<?php if($this->admin) { ?>
    <td>
      <input type="button" value="Delete" onclick="addGroup(<?php echo $group->group_id; ?>, 'delete')" />
    </td>
    <td><?php echo $group->last_update; ?></td>
<?php } ?>
  </tr>
<?php } ?>
</table>
<?php } ?>

<button type="submit" name="view" value="config">Create a new group</button>
</form>
