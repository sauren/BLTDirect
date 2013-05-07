<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

if(!CacheFile::isCached(Category::$cacheName)) {
	Category::Cache();
}

$categoryCache = CacheFile::load(Category::$cacheName);
$categoryOutput = array();

$categoryPriority = array();
$categoryPriority[] = 'LED Light Bulbs';
$categoryPriority[] = 'Halogen Light Bulbs';
$categoryPriority[] = 'Compact Fluorescent Lamps';
$categoryPriority[] = 'Fluorescent Tubes';
$categoryPriority[] = 'Energy Saving Light Bulbs';
$categoryPriority[] = 'Metal Halide Light Bulbs';

foreach($categoryPriority as $priority) {
	$index = 0;

	while($index < count($categoryCache)) {
		if($priority == $categoryCache[$index]) {
			$categoryOutput[] = $categoryCache[$index];
			$categoryOutput[] = $categoryCache[$index+1];
			
			array_splice($categoryCache, $index, 2);

			break;
		}
		
		$index += 2;
	}
}

for($i=0; $i<count($categoryCache); $i=$i+2) {
	$categoryOutput[] = $categoryCache[$i];
	$categoryOutput[] = $categoryCache[$i+1];
}

$categoryOutput[] = 'Projector Lamps';
$categoryOutput[] = './Projector-Lamps.php';

$GLOBALS['Cache']['Categories'] = $categoryOutput;

unset($categoryCache);
unset($categoryOutput);
unset($categoryPriority);