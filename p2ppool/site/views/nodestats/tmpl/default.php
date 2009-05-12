<?php // no direct access
defined('_JEXEC') or die('Restricted access');

$titles = array("Type", "Consis.", "Cons", "Virtual IP", "Namespace");
$fields = array("type", "consistency", "cons", "virtual_ip", "namespace");
?>
<style type="text/css">
table.sortable thead {
    background-color:#eee;
    color:#666666;
    font-weight: bold;
    cursor: default;
}
</style>

<table class="sortable">
  <tr>
    <th>Host name / IP</th>
<?php foreach($titles as $title) { ?>
    <th><?php echo $title; ?></th>
<?php } ?>
  </tr>
<?php foreach($this->nodes as $node) { ?>
  <tr>
    <td><?php
if($node["name"]) {
  echo $node["name"];
} else {
  echo $node["ip"];
}
    ?></td>
<?php   foreach($fields as $field) { ?>
    <td><?php echo $node[$field]; ?></td>
<?php   } ?>
  </tr>
<?php } ?>
</table>
