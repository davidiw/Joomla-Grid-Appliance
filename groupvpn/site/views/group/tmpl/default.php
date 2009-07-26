<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
$titles = array("Username", "Name", "E-mail", "Reason for joining");
$values = array("username", "name", "email", "reason");
?>

<script type="text/javascript">
function secureSubmit() {
  var form = document.getElementById("form");
  form.action = "<?php echo JRoute::_("index.php", false, 1); ?>";
  form.submit();
}

function submitTask(action) {
  var form = document.getElementById("form");
  var task = document.getElementById("task");
  if(action == "editConfig") {
    var ele = document.createElement("input");
    ele.type = "hidden";
    ele.name = "view";
    ele.value = "config";
    form.appendChild(ele);

    task.value = "viewHandler";
  } else {
    task.value = action;
  }

  form.submit();
}
</script>

<form action="index.php" method="post" id="form">

Group name: <?php echo $this->group->group_name; ?><br/>
Description: <?php echo $this->group->description; ?><br />
Members:
<table border=1 >
  <tr>
<?php foreach($titles as $title) { ?>
    <td><?php echo $title; ?></td>
<?php } ?>
    <td>Status</td>
<?php if($this->admin) { ?>
    <td>Action</td>
<?php } ?>
  </tr>
<?php
foreach($this->members as $member) { 
  if($member->revoked) {
    $status = "revoked";
  } else if($member->admin) {
    $status = "admin";
  } else if($member->member) {
    $status = "member";
  } else if($this->admin) {
    if($member->request) {
      $status = "requested";
    } else {
      $status = "denied";
    }
  } else {
    continue;
  }
?>
  <tr>
<?php foreach($values as $value) { ?>
    <td><?php echo $member->$value; ?></td>
<?php } ?>
    <td><?php echo $status; ?></td>
<?php if($this->admin) { ?>
    <td><table><?php
if($member->admin) { ?>
  <tr><td>Demote: </td><td><input type="checkbox" name="demote[]" value="<? echo $member->user_id; ?>" /></td></tr>
  <tr><td>Revoke: </td><td><input type="checkbox" name="revoke[]" value="<? echo $member->user_id; ?>" /></td></tr>
<?php
} else if($member->member) { ?>
  <tr><td>Promote: </td><td><input type="checkbox" name="promote[]" value="<? echo $member->user_id; ?>" /></td></tr>
  <tr><td>Revoke: </td><td><input type="checkbox" name="revoke[]" value="<? echo $member->user_id; ?>" /></td></tr>
<?php
}
if(!$member->revoked and !$member->member and !$member->admin) { ?>
  <tr><td>Accept: </td><td><input type="checkbox" name="accept[]" value="<? echo $member->user_id; ?>" /></td></tr>
<?php
}
  ?></table></td>
<?php } ?>
  </tr>
<?php } ?>
</table>

<?php if($this->admin) { ?>
  <input type="button" value="Submit changes" onclick="submitTask('manage')" />
  <input type="button" value="Delete" onclick="submitTask('deleteGroup')" />
  <input type="button" value="Edit Config" onclick="submitTask('editConfig')" />
<?php } ?>
  <input type="hidden" name="group_id" value="<?php echo $this->group->group_id; ?>" />
  <input type="hidden" name="option" value="com_groupvpn" />
  <input type="hidden" id="task" name="task" value="downloadConfig" />
  <input type="button" value="Download Config" onclick="secureSubmit()" />
</form>
