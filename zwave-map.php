<?php

// Requires: libgv-php5
// echo extension=gv.so > /etc/php5/mods-available/gv.so && php5enmod gv


if($argc !== 3) {
	die("Usage: {$argv[0]} OZW.log zwcfg.xml\n");
}
$ozwLog = $argv[1];
$zwcfg = $argv[2];
$controllerId = 1;



function GetNodeColor($hops) {
	if($hops === 1) {
		return 'forestgreen';
	}
	else if($hops === 2) {
		return 'darkturquoise';
	}
	else if($hops === 3) {
		return 'gold2';
	}
	else if($hops === 4) {
		return 'darkorange';
	}
	else if($hops === 5) {
		return 'red';
	}
	return 'black';
	
}

function GetEdgeColor($hops) {
	if($hops === 1) {
		return 'forestgreen';
	}
	else if($hops === 2) {
		return 'darkturquoise';
	}
	else if($hops === 3) {
		return 'gold2';
	}
	else if($hops === 4) {
		return 'darkorange';
	}
	else if($hops === 5) {
		return 'red';
	}
	return 'black';
}



$nodes = array();


echo "Reading XML\n";
$xml = file_get_contents($zwcfg);
$xml = new SimpleXMLElement($xml);
foreach($xml->Node as $v) {
	$id = (int) $v['id'];
	$name = $v['name'];
	$name = reset($name);	// No clue why I get an array back

	if(empty($name)) {
		$name = "{$v->Manufacturer['name']} {$v->Manufacturer->Product['name']}";
	}
	echo "{$id} => {$name}\n";

	$nodes[$id]['name'] = $name;
}



/*
2017-10-14 12:02:31.336 Info, Node001,     Neighbors of this node are:
2017-10-14 12:02:31.336 Info, Node001,     Node 2
2017-10-14 12:02:31.336 Info, Node001,     Node 3
*/
echo "Reading OZW log\n";
$buf = file_get_contents($ozwLog);
$buf = explode(PHP_EOL, $buf);
$neighbors = FALSE;
foreach($buf as $line) {

	if(preg_match('/.*Info, Node([0-9]{3}), +Neighbors of this node are:$/', $line, $matches) === 1) {
		$node = (int) $matches[1];

		echo $line . PHP_EOL;

		if(!isset($nodes[$node])) {
			die("Node {$node} not found in xml\n");
		}

		$nodes[$node]['neighbors'] = array();
		$neighbors = TRUE;
		continue;
	}

	if($neighbors === TRUE && preg_match('/.*Info, Node([0-9]{3}), +Node ([0-9]+)$/', $line, $matches) === 1) {
		$node = (int) $matches[1];
		$neighbor = (int) $matches[2];
	
		echo $line . PHP_EOL;

		if(!isset($nodes[$node])) {
			die("Node {$node} not initialized\n");
		}

		if(!in_array($neighbor, $nodes[$node]['neighbors'])) {
			$nodes[$node]['neighbors'][] = $neighbor;
		}
	} else {
		$neighbors = FALSE;
	}
}


echo "Calculating hops\n";
$nodes[$controllerId]['hops'] = 0;	// The controller obviously has 0 hops
// Z-wave supports max 4 hops
for($maxHops = 1 ; $maxHops <= 4 ; $maxHops++) {
	foreach($nodes as $id => $n) {
		if(isset($n['hops'])) {
			continue;
		}

		if(!isset($n['neighbors'])) {	// Should not happen, this is a workaround
			$nodes[$id]['hops'] = 5;
			continue;
		}

		$hops = FALSE;
		foreach($n['neighbors'] as $neighbor) {
			if(!isset($nodes[$neighbor]['hops'])) {
				continue;
			}
			if($hops === FALSE || $nodes[$neighbor]['hops']+1 < $hops) {
				$hops = $nodes[$neighbor]['hops']+1;
			}
		}
		if($hops <= $maxHops) {
			$nodes[$id]['hops'] = $hops;
			echo "{$id} has {$hops} hops to the controller\n";
		}
	}
}



echo "Rendering graph\n";
require_once('/usr/share/php/libgv-php5/gv.php');
$gv = gv::graph ('zwave-map');
foreach($nodes as $id => $n) {
	$nodes[$id]['nodeHandle'] = gv::node($gv, $id);
	gv::setv($nodes[$id]['nodeHandle'], 'label', $n['name']);

	if($id === $controllerId) {
		gv::setv($nodes[$id]['nodeHandle'], 'fontcolor', 'white');
		gv::setv($nodes[$id]['nodeHandle'], 'fillcolor', 'gray50');
		gv::setv($nodes[$id]['nodeHandle'], 'style', 'filled');
		gv::setv($nodes[$id]['nodeHandle'], 'color', 'black');
		continue;
	}
	gv::setv($nodes[$id]['nodeHandle'], 'color', GetNodeColor($n['hops']));
}
$addedEdges = array();
foreach($nodes as $id => $n) {
	if(!empty($n['neighbors'])) foreach($n['neighbors'] as $neighbor) {
		// Sort nodes and check that the connection isn't already drawn
		$n1 = min(array($id, $neighbor));
		$n2 = max(array($id, $neighbor));
		if($n1 === $n2) {	// It seems weird that this happens
			continue;
		}
		$edge = "{$n1}:{$n2}";
		if(isset($addedEdges[$edge])) {
			continue;
		}
		$addedEdges[$edge] = TRUE;

		$edgeHandle = gv::edge($n['nodeHandle'], $nodes[$neighbor]['nodeHandle']);

		$hops = min(array($nodes[$n1]['hops'], $nodes[$n2]['hops']));
		gv::setv($edgeHandle, 'color', GetEdgeColor($hops+1));
	}
}
gv::write($gv, 'zwave-map.dot');

echo "cat zwave-map.dot |dot -Tsvg -ozwave-map.svg\n";
