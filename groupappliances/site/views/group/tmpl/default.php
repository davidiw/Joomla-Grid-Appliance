<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
$titles = array("Username", "Name", "E-mail", "Reason for joining");
$values = array("username", "name", "email", "reason");
?>

<script type="text/javascript">
function secureSubmit() {
  var form = document.getElementById("form");
  form.action = "<?php echo JRoute::_("index.php", true, 1); ?>";
  form.submit();
}

function submitTask(action) {
  var form = document.getElementById("form");
  var task = document.getElementById("task");
  task.value = action;
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

<?php if($this->member) { if($this->admin) { ?>
  <input type="button" value="Submit changes" onclick="submitTask('manage')" />
  <input type="button" value="Delete" onclick="submitTask('deleteGroup')" />
  Generate Floppy:
<?php } ?>
  <select name="floppy_type">
    <option value="Client">Client</option>
    <option value="Worker">Worker</option>
<?php if($this->admin) { ?>
    <option value="Server">Server</option>
<?php } ?>
  </select>
  <select name="arch">
    <option value="x86">x86-32</option>
    <option value="x64">x86-64</option>
  </select>
  <input type="hidden" name="ga_id" value="<?php echo $this->group->ga_id; ?>" />
  <input type="hidden" name="group_id" value="<?php echo $this->group->group_id; ?>" />
  <input type="hidden" name="option" value="com_groupappliances" />
  <input type="hidden" id="task" name="task" value="downloadFloppy" />
  <input type="button" value="Download" onclick="secureSubmit()" /> 
<?php } ?>
</form>
