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
}

function addUnloadEvent(func) {
  var oldfunc = window.unload;
  if(typeof window.unload != 'function') {
    window.unload = func;
  } else {
    window.unload = function() { oldfunc(); func(); }
  }
}

function mapLoad(ns) {
  if (GBrowserIsCompatible()) {
    var map = new GMap2(document.getElementById("map"));
    map.setCenter(new GLatLng(0, 0), 1);
    map.addControl(new GLargeMapControl());
    map.addControl(new GOverviewMapControl());
    
    for(idx in coords[ns]) {
      map.addOverlay(new GMarker(GLatLng.fromUrlValue(coords[ns][idx])));
    }
    if(ns == "All") {
      for(idx in namespaces) {
        if(namespaces[idx] == "All") {
          continue;
        }
        for(jdx in coords[namespaces[idx]]) {
          var latlong = coords[namespaces[idx]][jdx];
          map.addOverlay(new GMarker(GLatLng.fromUrlValue(latlong)));
        }
      }
    }
  }
}

function updateMap() {
  var select = document.getElementById("namespaces");
  mapLoad(select.options[select.selectedIndex].value);
}

function mapPrep() {
  <?php
  if($this->coordinates) {
    foreach($this->coordinates as $coord) {
      if(!empty($coord[0])) {
  ?>
        var node_ns = "<? echo $coord[0]; ?>";
        if(coords[node_ns] == null) {
          coords[node_ns] = [];
          namespaces.push(node_ns);
        }
        coords[node_ns].push("<? echo $coord[1]; ?>");
  <?php
      } else {
?>
        coords["All"].push("<? echo $coord[1]; ?>");
<?php
      }
    }
  }
  ?>

  var select = document.getElementById("namespaces");
  for(val in namespaces) {
    var ele = document.createElement("option");
    ele.setAttribute("value", namespaces[val]);
    ele.appendChild(document.createTextNode(namespaces[val]));
    select.appendChild(ele);
  }

  mapLoad("All");
}

var namespaces = ["All"];
var coords = [];
coords["All"] = [];
addLoadEvent(mapPrep);
addUnloadEvent(GUnload);
</script>

<p>
<?php echo $this->description; ?>
</p>
<div id="map" style="width: 720px; height: 510px"></div>
<p>
Nodes with coordinates: <?php echo count($this->coordinates); ?><br />
Total node count: <?php echo $this->node_count; ?><br />
Ring consistency: <?php echo $this->consistency; ?><br />
Last update: <?php echo $this->date; ?><br />
Select IPOP Namespace to display:
<select id="namespaces" onchange="updateMap()"/>
</p>
