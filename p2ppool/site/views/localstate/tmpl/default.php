<?php // no direct access
defined('_JEXEC') or die('Restricted access');

$titles = array("Task", "Start time", "PID");
$fields = array("task", "start_time", "pid");
?>

<table border="1">
  <tr><td>Pool</td><td>Running</td><td>Uninstalled</td></tr>
  <tr>
    <td><?php echo $this->pool; ?></td>
    <td><?php echo $this->running; ?></td>
    <td><?php echo $this->uninstall; ?></td>
  </tr>
</table>
<?php if($this->state and count($this->state > 0)) { ?>
<table border="1">
  <tr>
<?php foreach($titles as $title) { ?>
    <td><?php echo $title; ?></td>
<?php } ?>
  </tr>
<?php foreach($this->state as $state) { ?>
  <tr>
<?php   foreach($fields as $field) { ?>
    <td><?php echo $state[$field]; ?></td>
<?php   } ?>
  </tr>
<?php } ?>
</table>
<?php } ?>
