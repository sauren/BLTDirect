<?php
require_once('../lib/common/appHeadermobile.php');
$articleId = id_param('id');

if(empty($articleId)) {
	redirectTo('articles.php');
}

$data = new DataQuery(sprintf("SELECT * FROM article_category WHERE Article_Category_ID=%d and Is_Active = 'Y'", mysql_real_escape_string($articleId)));
if($data->TotalRows == 0) {
	redirectTo('articles.php');
}
$category = $data->Row['Category_Title'];
$categoryMetaKeywords = $data->Row['Meta_Keywords'];
$categoryMetaDescription = $data->Row['Meta_Description'];

$data->Disconnect();
	include("ui/nav.php");
	include("ui/search.php");
?>
<div class="cartmiddle"><span style="font-size:16px;color:#333; margin-top:10px;"><?php print $category; ?></span></div>
<div class="maincontent">
<div class="maincontent1">
<!--              <p class="breadCrumb"><a href="index.php">Home</a> | <a href="articles.php">Press Releases/Articles</a></p>-->
              <div class="articles" style="width:100%;">
			  <?php
				$data = new DataQuery(sprintf("SELECT * FROM article WHERE Article_Category_ID=%d AND Is_Active='Y' ORDER BY Created_On DESC", mysql_real_escape_string($articleId)));

               
				while($data->Row) {
					$adata=stripslashes($data->Row['Article_Description']);
					$articledata=str_replace('width="640" height="360"','width="100%" height="240"',$adata);
					print sprintf('<h2>%s</h2><p><span class="sub-text">%s</span></p><p class="video_article">%s</p>', $data->Row['Article_Title'], cDatetime($data->Row['Created_On'], 'shortdatetime'), $articledata);

					$data2 = new DataQuery(sprintf("SELECT * FROM article_download WHERE Article_ID=%d", mysql_real_escape_string($data->Row['Article_ID'])));
					if($data2->TotalRows) {
						print '<p class="download">Downloads</p>';
						print '<ul>';

						while($data2->Row) {
							print sprintf('<li><a target="_blank" href="downloads/articles/%s" title="Download %d">%s</a></li>', $data2->Row['File_Name'], $data2->Row['Title'], $data2->Row['Title']);
							$data2->Next();
						}

						print '</ul>';
					}
					$data2->Disconnect();

					$data->Next();
				}

				$data->Disconnect();
				?>
				</div>
                </div>
                </div>
                 <?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>