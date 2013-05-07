<?php require_once('../lib/common/appHeadermobile.php');
	include("ui/nav.php");
	include("ui/search.php");
?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Press Releases/Articles</span></div>
<div class="maincontent">
<div class="maincontent1">
<!--              <p class="breadCrumb"><a href="index.php">Home</a></p>-->
			  	<?php
				$data = new DataQuery(sprintf("SELECT * FROM article_category WHERE Is_Active ='Y' ORDER BY Created_On DESC"));
				if($data->TotalRows > 0) {
					echo '<ul>';
					while($data->Row) {
						print sprintf('<li style="padding-top:8px;"><strong><a href="article.php?id=%d" title="%s">%s</a></strong></li>', $data->Row['Article_Category_ID'], $data->Row['Category_Title'], $data->Row['Category_Title']);
						$data->Next();
					}
					echo '</ul>';
				} else {
					echo '<p>There are no press releases/articles at this time.</p>';
				}
				$data->Disconnect();
				?>
                </div>
                </div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>