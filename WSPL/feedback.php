<?php require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Customer Feedback</span></div>
<div class="maincontent">
<div class="maincontent1">
              <p>Hear what our customers have to say about us.</p>
              <p style="text-align: center;"><a href="http://www.google.co.uk/products/seller?cmi=63124147968999424&amp;q=bltdirect&amp;hl=en&amp;ei=3Sj-S_bUDpS82ASYhKmLCA&amp;sa=X&amp;ved=0CAgQlQgwADgA"><strong>View our Google checkout ranking!</strong></a></p><br />

              <div class="articles">
			  <?php
				$data = new DataQuery(sprintf("SELECT * FROM feedback ORDER BY Created_On DESC"));

				while($data->Row) {
					print sprintf('<div style="background-color: #efefef; padding: 10px;"><p style="margin: 0;">%s</p></div><br />', nl2br($data->Row['Description']));

					$data->Next();
				}

				$data->Disconnect();
				?>
				</div>           
  
  
  
  
  
  
</div>
</div>


<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>