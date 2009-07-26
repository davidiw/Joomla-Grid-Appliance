<?php // no direct access
defined('_JEXEC') or die('Restricted access');
?>
<table>
  <tr><td>Username:</td><td><?php echo $this->user->username; ?></td></tr>
  <tr><td>Name:</td><td><?php echo $this->user->name; ?></td></tr>
  <tr><td>Organization:</td><td><?php echo $this->user->organization; ?></td></tr>
  <tr><td>Department:</td><td><?php echo $this->user->organizational_unit; ?></td></tr>
  <tr><td>Country:</td><td><?php echo $this->user->country; ?></td></tr>
  <tr><td>Phone number:</td><td><?php echo $this->user->phone; ?></td></tr>
  <tr><td>Ethnicity:</td><td><?php echo $this->user->ethnicity; ?></td></tr>
  <tr><td>Reason for Account:</td><td><?php echo $this->user->reason; ?></td></tr>
</table>
<p>
<a href="index.php?option=com_groupvpn&view=group&group_id=<?php echo $this->group_id; ?>">Go back</a>
</p>
