<?php
	require_once('../lib/common/appHeadermobile.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
include("ui/nav.php");
include("ui/search.php");
?>
<div class="maincontent">
<div class="maincontent1">
  <?php
                    $data = new DataQuery(sprintf("SELECT * FROM article WHERE Article_Category_ID=%d AND Is_Active='Y' ORDER BY Created_On DESC", TERM_ARTICLE));

                        while($data->Row) {
                            print sprintf('<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">%s</span></div><p>%s</p>', $data->Row['Article_Title'], stripslashes($data->Row['Article_Description']));
                            $data->Next();
                        }

                    $data->Disconnect();?>
                    </div>
                    </div>
 <?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>