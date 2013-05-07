<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Brochure.php');

$brochure = new Brochure();
$brochureCacheName = 'template_brochure';
$brochureId = 0;

if(!CacheFile::isCached($brochureCacheName)) {
	$brochureId = Brochure::GetActiveBrochureID();
	
	CacheFile::save($brochureCacheName, $brochureId);
}

$brochureCache = CacheFile::load($brochureCacheName);

if($brochureCache !== false) {
	$brochureId = isset($brochureCache[0])?$brochureCache[0]:null;
	
	if(!$brochure->Get($brochureId)) {
		$brochureId = Brochure::GetActiveBrochureID();
		
		$brochure->Get($brochureId);
	}
}

$GLOBALS['Cache']['Brochure'] = $brochure;

unset($brochure);
unset($brochureCache);
unset($brochureCacheName);
unset($brochureId);