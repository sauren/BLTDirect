<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

header("Content-type: image/jpeg");

$discountText = sprintf('%d%% DISCOUNT', 5);
$couponText = 'ABC-DEF-123';

$image = imagecreatefromgif("../images/email/template/panel/discount.gif");
$white = imagecolorallocate($image, 255, 255, 255);

imagestring($image, 5, (imagesx($image) - (9 * strlen($discountText))) / 2, 85, $discountText, $white);
imagestring($image, 5, 27, 224, $couponText, $white);

imagejpeg($image, null, 100);
imagedestroy($image);