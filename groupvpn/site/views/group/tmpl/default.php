<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
$titles = array("Name", "E-mail");
$values = array("name", "email");
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

function reviewAccount(uid) {
  var form = document.getElementById("form");
  var ele = document.createElement("input");
  ele.type = "hidden";
  ele.name = "user_id";
  ele.value = uid;
  form.appendChild(ele);
  var task = document.getElementById("task");
  task.value = "reviewAccount";
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

Group name: <?php echo $this->group->group_name; ?><br/>
Description: <?php echo $this->group->description; ?><br />
Members:
<table border=1 class="sortable">
  <tr>
    <th>Username</th>
<?php foreach($titles as $title) { ?>
    <th><?php echo $title; ?></th>
<?php } ?>
    <th>Status</th>
<?php if($this->admin) { ?>
    <th>Action</th>
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
    <td <?php if($this->admin) echo 'onclick="reviewAccount(\''.$member->user_id.'\')"' ?>>
      <?php if($this->admin) { ?><font color="blue"><?php } ?>
      <?php echo $member->username; ?>
      <?php if($this->admin) { ?></font><?php } ?>
    </td>
<?php foreach($values as $value) { ?>
    <td><?php echo $member->$value; ?></td>
<?php } ?>
    <td><?php echo $status; ?></td>
<?php if($this->admin) { ?>
    <td><table><?php
if($member->admin) { ?>
  <tr>
    <td>Demote: </td><td><input type="checkbox" name="demote[]" value="<? echo $member->user_id; ?>" /></td>
    <td>Revoke: </td><td><input type="checkbox" name="revoke[]" value="<? echo $member->user_id; ?>" /></td>
  </tr>
<?php
} else if($member->member) { ?>
  <tr>
    <td>Promote: </td><td><input type="checkbox" name="promote[]" value="<? echo $member->user_id; ?>" /></td>
    <td>Revoke: </td><td><input type="checkbox" name="revoke[]" value="<? echo $member->user_id; ?>" /></td>
  </tr>
<?php
}
if(!$member->revoked and !$member->member and !$member->admin and $member->request) { ?>
  <tr>
    <td>Accept: </td><td><input type="checkbox" name="accept[]" value="<? echo $member->user_id; ?>" /></td>
    <td>Deny: </td><td><input type="checkbox" name="deny[]" value="<? echo $member->user_id; ?>" /></td>
  </tr>
<?php
}
  ?></table></td>
<?php } ?>
  </tr>
<?php } ?>
</table>

<?php if($this->member) { ?>
<?php if($this->admin) { ?>
  <input type="button" value="Submit changes" onclick="submitTask('manage')" />
  <input type="button" value="Delete" onclick="submitTask('deleteGroup')" />
  <input type="button" value="Edit Config" onclick="submitTask('editConfig')" />
<?php } ?>
  <input type="hidden" name="group_id" value="<?php echo $this->group->group_id; ?>" />
  <input type="hidden" name="option" value="com_groupvpn" />
  <input type="hidden" id="task" name="task" value="downloadConfig" />
  <input type="button" value="Download Config" onclick="secureSubmit()" />
<?php } ?>
</form>
