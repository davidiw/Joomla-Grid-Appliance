<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
?>

<script type="text/javascript">
function action(task, group) {
  var form = document.getElementById("form");

  var task_ele = document.createElement("input");
  task_ele.type = "hidden";
  task_ele.name = "task";
  if(task == "view") {
    task_ele.value = "viewHandler";
    var view_ele = document.createElement("input");
    view_ele.type = "hidden";
    view_ele.name = "view";
    view_ele.value = "group";
    form.appendChild(view_ele);
  } else {
    task_ele.value = task;
  }

  if(task == "join") {
    var reason_in = document.getElementById("reason_" + group);
    var view_ele = document.createElement("input");
    view_ele.type = "hidden";
    view_ele.name = "reason";
    view_ele.value = reason_in.value;
    form.appendChild(view_ele);
  }

  form.appendChild(task_ele);

  var group_ele = document.createElement("input");
  group_ele.type = "hidden";
  group_ele.name = "ga_id";
  group_ele.value = group;
  form.appendChild(group_ele);

  form.submit();
}
</script>

<form action="index.php" method="post" id="form">
<input type="hidden" name="option" value="com_groupappliances" />
</form>

<?php if($this->groups) { ?>
<table border=1>
  <tr>
    <td>Group</td>
    <td>Description</td>
    <td>GroupVPN</td>
    <td>Reason for joining</td>
    <td>State</td>
    <td>Action</td>
  </tr>
<?php
if($this->my_groups) {
  foreach($this->my_groups as $group) {
?>
  <tr>
    <td>
      <a href="index.php?option=com_groupappliances&task=viewHandler&view=group&ga_id=<?php echo $group->ga_id; ?>"><?php
        echo $this->groups[$group->ga_id]->group_name; ?></a>
    </td>
    <td><?php echo $this->groups[$group->ga_id]->description; ?></td>
    <td><?php echo $this->groups[$group->ga_id]->gn; ?></td>
    <td><?php echo $group->reason; ?></td>
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
    <input type="button" value="Leave" onclick="action('leave', <?php echo $group->ga_id; ?>)" />
<?php } ?></td>
  </tr>
<?php
  }
}

foreach($this->groups as $group) {
  if($this->my_groups[$group->ga_id]) {
    continue;
  }
?>
  <tr>
    <td>
      <a href="index.php?option=com_groupappliances&task=viewHandler&view=group&ga_id=<?php echo $group->ga_id; ?>"><?php
        echo $group->group_name; ?></a>
    </td>
    <td><?php echo $group->description; ?></td>
    <td><?php echo $group->gn; ?></td>
    <td><input type="text" id="reason_<?php echo $group->ga_id; ?>" /></td>
    <td />
    <td><input type="button" value="Join" onclick="action('join', <?php echo $group->ga_id; ?>)" /></td>
  </tr>
<?php } ?>
</table>
<?php } ?>

<p>
Create a new group:
</p>
<form action="index.php" method="post" id="form">
<input type="hidden" name="option" value="com_groupappliances" />
<input type="hidden" name="task" value="create" />
<input type="text" name="group_name" value="group name" />
<textarea name="description">Group description</textarea>
<select name="group_id" id="group_id">
<?php foreach($this->groupvpns as $groupvpn) { ?> 
  <option value="<?php echo $groupvpn->group_id; ?>">
    <?php echo $groupvpn->group_name; ?>
  </option>
<?php } ?>
</select>
<input type="submit" value="Create group" />
</form>
