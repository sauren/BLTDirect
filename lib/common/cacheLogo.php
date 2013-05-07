<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Logo.php');

$logoCacheName = 'template_logo';
$logoSource = './images/template/logo_blt_1.jpg';
$logoModified = CacheFile::modified($logoCacheName);

if(($logoModified === false) || (($logoModified + 86400) < time())) {
	$logo = new Logo();
	$logo->GetActiveLogoID();

	if($logo->ID > 0) {
		$logo->Get();

		if(!empty($logo->Image->FileName) && file_exists($GLOBALS['LOGO_IMAGE_DIR_FS'].$logo->Image->FileName)) {
			$logoSource = $GLOBALS['LOGO_IMAGE_DIR_WS'].$logo->Image->FileName;
		}
	}

	CacheFile::save($logoCacheName, $logoSource);
	
	unset($logo);
}

$logoCache = CacheFile::load($logoCacheName);

if($logoCache !== false) {
	$logoSource = $logoCache[0];
}

$GLOBALS['Cache']['Logo'] = $logoSource;

unset($logoCache);
unset($logoCacheName);
unset($logoSource);