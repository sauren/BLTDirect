<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Our Products</span></div>
<ul class="menu">
<?php
$holidayPromos = new HolidayPromotion();
$isChristmas = false;
if($holidayPromos->IsChristmas()) {
$isChristmas = true;
}
$isHalloween = false;
if($holidayPromos->IsHalloween()) {
$isHalloween = true;
}
for($i=0; $i<count($GLOBALS['Cache']['Categories']); $i=$i+2) {
if($isChristmas) {
echo sprintf('<li><a class="WhiteLnkSideMenu" href=".%s" title="%s"%s>%s</a><li>', $GLOBALS['Cache']['Categories'][$i+1], htmlentities($GLOBALS['Cache']['Categories'][$i]), (stristr($GLOBALS['Cache']['Categories'][$i], 'Christmas')) ? ' class="christmas"' : '', htmlentities($GLOBALS['Cache']['Categories'][$i]));
} elseif($isHalloween) {
echo sprintf('<li><a href=".%s" title="%s"%s>%s</a></li>', $GLOBALS['Cache']['Categories'][$i+1], htmlentities($GLOBALS['Cache']['Categories'][$i]), (stristr($GLOBALS['Cache']['Categories'][$i], 'Halloween')) ? ' class="halloween"' : '', htmlentities($GLOBALS['Cache']['Categories'][$i]));
} else {
echo sprintf('<li><a onclick="location.href=\'.%s\';">%s</a></li>', $GLOBALS['Cache']['Categories'][$i+1], htmlentities($GLOBALS['Cache']['Categories'][$i]), htmlentities($GLOBALS['Cache']['Categories'][$i]));
}
}
?>
</ul>