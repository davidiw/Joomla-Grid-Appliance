<?php // no direct access
defined('_JEXEC') or die('Restricted access');
?>

<?php if($this->pool->pool) { ?>
You are in pool upgrading / editing mode.  Leaving the uploadable components blank
will leave them as they are.
<?php } ?>
<form action="<?php echo JROUTE::_("index.php", true, true); ?>" enctype="multipart/form-data" method="post" id="form">
  <input type="hidden" name="default_pool" value="0" />
  <input type="hidden" name="mkbundle" value="0" />
  <table>
    <tr>
      <td>Pool name:</td>
      <td><input type="text" name="pool"
            value="<?php echo $this->pool->pool; ?>"
            <?php if($this->pool->pool) { ?> readOnly="true" <?php } ?> />
      </td>
    </tr>
    <tr>
      <td>Description (appears above pool status):</td>
      <td><textarea name="description"><?php echo $this->pool->description; ?></textarea></td>
    </tr>
    <tr>
      <td>Namespace:</td>
      <td><input type="text" name="namespace" value="<?php echo $this->pool->namespace; ?>"/></td>
    </tr>
    <tr>
      <td>UDP Port (empty disables UDP):</td>
      <td><input type="text" name="udpport" value="<?php echo $this->pool->udpport; ?>"/></td>
    </tr>
    <tr>
      <td>TCP Port (empty disables TCP):</td>
      <td><input type="text" name="tcpport"  value="<? echo $this->pool->tcpport; ?>"/></td>
    </tr>
    <tr>
      <td>XML-RPC Port:</td>
      <td><input type="text" name="rpcport"  value="<? echo $this->pool->rpcport; ?>"/></td>
    </tr>
    <tr>
      <td>User name:</td>
      <td><input type="text" name="user_name" value="<? echo $this->pool->user_name; ?>"/></td>
    </tr>
    <tr>
      <td>Install path:</td>
      <td><input type="text" name="install_path" value="<?php
if($this->pool->install_path) {
  echo $this->pool->install_path;
} else {
  echo "/tmp/p2p";
}
?>"/></td>
    </tr>
    <tr>
      <td>Line delimited list of nodes:</td>
      <td><input type="file" name="nodes" /></td>
    </tr>
    <tr>
      <td>Zip files of P2P software:</td>
      <td><input type="file" name="files" /></td>
    </tr>
    <tr>
      <td>SSH Key</td>
      <td><input type="file" name="ssh_key" /></td>
    </tr>
    <tr>
      <td>Default pool</td>
      <td><input type="checkbox" name="default_pool" value="1"
<?php if($this->pool->default_pool) { ?>
            checked="on"
<?php } ?>/></td>
    </tr>
    <tr>
      <td>Use mkbundle</td>
      <td><input type="checkbox" name="mkbundle" value="1"
<?php if($this->pool->mkbundle) { ?>
            checked="on"
<?php } ?>/></td>
    </tr>
    <tr>
      <td>Test system</td>
      <td><input type="checkbox" name="test" value="1"
<?php if($this->pool->test) { ?>
            checked="on"
<?php } ?>/></td>
    </tr>
  </table>
  <input type="hidden" name="task" value="<?php echo $this->task; ?>" />
  <input type="hidden" name="option" value="com_p2ppool" />
  <input type="hidden" name="pool_id" value="<?php echo $this->pool->pool_id; ?>" />
  <input type="submit" value="Submit" />
</form>
