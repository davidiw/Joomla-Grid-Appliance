<?php // no direct access
defined('_JEXEC') or die('Restricted access');
?>
<script type="text/javascript">
function Submit(res) {
  if(res) {
    document.getElementById("form").submit();
  } else {
    window.location = "index.php";
  }
}
</script>

<p>
Welcome <?php echo $this->user->name; ?>, if you would like to join
"<?php echo $this->group->group_name; ?>" please
<?php if($this->group->tos) { ?>
read the following, enter the required information, and press the "I Agree"
button on the bottom.
<?php } else { ?>
enter the required information and press "Join" on the bottom.
<?php } ?>
<p/>

<?php if($this->group->tos) { ?>
<p>
  <center>
  <textarea rows="25" cols="80" readonly="true"><?php echo $this->group->tos; ?></textarea>
  </center>
</p>
<?php } ?>

<p />
<p />

<form action="index.php" method="post" id="form">
  <input type="hidden" name="option" value="com_groupvpn">
  <input type="hidden" name="task" value="joinGroup">
  <input type="hidden" name="group_id" value="<?php echo $this->group->group_id; ?>" />
  <p>
<table>
<?php if($this->group->detailed_registration) { ?>
  <tr>
    <td>Organization:</td>
    <td><input type="text" name="organization"/></td>
  </tr>
  <tr>
    <td>Department:</td>
    <td><input type="text" name="organizational_unit" /></td>
  </tr>
  <tr>
    <td>Country:</td>
    <td><input type="text" name="country" /></td>
  </tr>
  <tr>
    <td>Phone number:</td>
    <td><input type="text" name="phone" /></td>
  </tr>
  <tr>
    <td>Ethnicity:</td> 
    <td><select name="ethnicity">
      <option value="Anonymous">Anonymous</option>
      <option value="American Indian or Alaskan Native">American Indian or Alaskan Native</option>
      <option value="Asian">Asian</option>
      <option value="Black or African American">Black or African American</option>
      <option value="Hispanic">Hispanic</option>
      <option value="Pacific Islander">Pacific Islander</option>
      <option value="White">White</option>
    </select> </td>
  </tr>
<?php } ?>
  <tr>
    <td>Reason for Account:</td>
    <td><textarea name="reason" rows="4" cols="32"></textarea></td>
  </tr>
</table>
  </p>
  <p />
  <p>
<?php if($this->group->tos) { ?>
    <input type="button" name="agree" value="I Agree" onclick="Submit(true)" />
<?php } else { ?>
    <input type="button" name="agree" value="Join" onclick="Submit(true)" />
<?php } ?>
    <input type="button" name="agree" value="No Thanks" onclick="Submit(false)"/>
  </p>
</form>
