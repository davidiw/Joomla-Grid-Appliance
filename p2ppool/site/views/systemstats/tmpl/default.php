<?php // no direct access
defined('_JEXEC') or die('Restricted access')
?>

<table>
  <tr><td>Date</td><td>Total Nodes</td><td>Consistency</td></tr>
<?php foreach($this->stats as $row) { ?>
  <tr>
<?php   foreach($row as $entry) { ?>
    <td><?php echo $entry; ?></td>
<?php   } ?>
  </tr>
<?php } ?>
</table>
