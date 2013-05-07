<?php
require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");
?>
<div class="maincontent">
<div class="maincontent1">
<?php
$data = new DataQuery(sprintf("SELECT * FROM article WHERE Article_Category_ID=%d AND Is_Active='Y' ORDER BY Created_On DESC", LAMP_BASE_POPULAR));
while($data->Row) {
$ldata=stripslashes($data->Row['Article_Description']);
$lampdata=str_replace('width="560" height="315"','width="100%" height="240"',$ldata);
print sprintf('<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">%s</span></div><p>%s</p>', $data->Row['Article_Title'], $lampdata);
$data->Next();
}
$data->Disconnect();
?>			
<div class="clear"></div>
</div>
</div>
<?php 
include("ui/footer.php");
include('lib/common/appFooter.php'); ?>