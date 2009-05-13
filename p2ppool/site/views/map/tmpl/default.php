<?php // no direct access
defined('_JEXEC') or die('Restricted access')
?>

<script type="text/javascript">
function addLoadEvent(func) {
  var oldonload = window.onload;
  if(typeof window.onload != 'function') {
    window.onload = func;
  } else {
    window.onload = function() { oldonload(); func(); }
  }
  alert(oldonload());
}

function addUnloadEvent(func) {
  var oldfunc = window.unload;
  if(typeof window.unload != 'function') {
    window.unload = func;
  } else {
    window.unload = function() { oldfunc(); func(); }
  }
}

function mapLoad() {
  if (GBrowserIsCompatible()) {
    var map = new GMap2(document.getElementById("map"));
    map.setCenter(new GLatLng(0, 0), 1);
    map.addControl(new GLargeMapControl());
    map.addControl(new GOverviewMapControl());

<?php foreach($this->coordinates as $coord) { ?>
    map.addOverlay(new GMarker(new GLatLng(<?php echo $coord; ?>)));;
<?php } ?>
  }
}

addLoadEvent(mapLoad);
addUnloadEvent(GUnload);
</script>

<p>
<?php echo $this->description; ?>
</p>
<div id="map" style="width: 720px; height: 510px"></div>
<p>
Nodes above: <?php echo count($this->coordinates); ?><br />
Total node count: <?php echo $this->node_count; ?><br />
Ring consistency: <?php echo $this->consistency; ?><br />
Last update: <?php echo $this->date; ?><br />
</p>
