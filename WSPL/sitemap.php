<?php
require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

$categories = array();

$data = new DataQuery("SELECT Category_ID, Category_Parent_ID, Category_Title, Meta_Title FROM product_categories WHERE Is_Active='Y'");
while($data->Row) {
	if(!isset($categories[$data->Row['Category_Parent_ID']])) {
		$categories[$data->Row['Category_Parent_ID']] = array();
	}
	
	$categories[$data->Row['Category_Parent_ID']][] = $data->Row;
	
	$data->Next();	
}
$data->Disconnect();

function listCategories($catId) {
	global $categories;
	
	$txt = '';
	$subCategory = new Category();

	if(isset($categories[$catId])) {
		$txt .= '<ul style="font-size:14px;">';
		
		foreach($categories[$catId] as $row) {
			$subCategory->ID = $row['Category_ID'];
			$subCategory->Name = $row['Category_Title'];
			$subCategory->MetaTitle = $row['Meta_Title'];

			$url = $subCategory->GetUrl();

			$txt .= sprintf('<li><a href="%s" title="%s">%s</a></li>', $url, $row['Meta_Title'], $row['Category_Title']);
			$txt .= listCategories($row['Category_ID']);
		}
		
		$txt .= '</ul>';
	}

	return $txt;
}
?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">BLT Direct Sitemap</span></div>
<div class="maincontent">
<div class="maincontent1">
					<table border="0" width="100%" cellspacing="0" cellpadding="0">
					 <tr>
					  <td width="100%">
						<h3>Product Categories</h3>
						<?php
						if(file_exists('../cache/sitemap.dat')) {
							include('../cache/sitemap.dat');
						} else {
							echo listCategories(0);
						}
						?>
					   </td></tr>
						<tr><td width="100%">
						<h3>Customer Account Centre</h3>
						<ul style="font-size:14px;">
						  <li><a href="accountcenter.php">My Account</a></li>
						  <li><a href="orders.php">My Order History</a></li>
						  <li><a href="profile.php">My Profile</a></li>
						  <li><a href="businessProfile.php">My Business Profile</a> </li>
						  <li><a href="changePassword.php">Change Password </a></li>
						  <li><a href="cart.php">View Shopping Cart  </a></li>
						</ul>
						<h3>Information Pages</h3>
						<ul style="font-size:14px;">
					      <li><a href="company.php">About BLT Direct</a></li>
					      <li><a href="lampBaseExamples.php">Lamp Base Examples</a></li>
					      <li><a href="deliveryRates.php">Delivery Rate Information</a></li>
					      <li><a href="security.php">Security at BLT Direct</a></li>
					      <li><a href="terms.php">Terms &amp; Conditions</a></li>
					    <li><a href="privacy.php">Privacy Policy</a>  </li>
					    </ul>					    <h3>Support Pages </h3>
					    <ul style="font-size:14px;">
					        <li><a href="support.php">Contact Us</a>     </li>
			            </ul>
			            
			            <h3>Landing Pages</h3>
						<ul style="font-size:14px;">
							<?php
							$data = new DataQuery(sprintf("SELECT name FROM product_landing ORDER BY name ASC"));
							while($data->Row) {
								echo sprintf('<li><a href="/%s">%s</a></li>', str_replace(' ', '-', strtolower(trim($data->Row['name']))), $data->Row['name']);
									
								$data->Next();	
							}
							$data->Disconnect();
							?>					    
					    </ul>
					 </td></tr></table>
                    </div>
                    </div>
  <?php include("ui/footer.php")?>
  <?php include('../lib/common/appFooter.php'); ?>