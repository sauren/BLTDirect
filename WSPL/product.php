<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Bubble.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryBreadCrumb.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Contact.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Customer.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Enquiry.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/EnquiryLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCookie.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductImageExampleRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductVideo.php');
require_once($GLOBALS['DIR_WS_ROOT'] . 'lib/common/yt_video_feed_wspl.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'packages/Securimage/securimage.php');

if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])){
	$url = ($GLOBALS['USE_SSL'])?$GLOBALS['HTTPS_SERVER']:$GLOBALS['HTTP_SERVER'];
	$self = substr($_SERVER['PHP_SELF'], 1);
	$qs = '';
	if(!empty($_SERVER['QUERY_STRING'])){
		$qs = '?' . $_SERVER['QUERY_STRING'];
	}
	redirect(sprintf("Location: %s%s%s", $url, $self, $qs));
}

function getCategories($id) {
	$items = array($id);
	
	$data = new DataQuery(sprintf("SELECT Category_Parent_ID FROM product_categories WHERE Category_ID=%d", mysql_real_escape_string($id)));
	if($data->TotalRows > 0) {
		if($data->Row['Category_Parent_ID'] > 0) {
			$items = array_merge($items, getCategories($data->Row['Category_Parent_ID']));
		}
	}
	$data->Disconnect();
	
	return $items;
}

$tab = param('tab', 'overview');

$product = new Product();

if(param('pid')) {
	$product->ID = str_replace($GLOBALS['PRODUCT_PREFIX'], '', param('pid'));

	if(!is_numeric($product->ID) || !$product->Get($product->ID)) {
		redirect("Location: index.php");
	}

	if(($product->IsActive == 'N') || ($product->IsDemo == 'Y')) {
		redirect("Location: index.php");
	}
	if($product->Discontinued == 'Y') {
		if($product->SupersededBy > 0) {
			redirect(sprintf("Location: product.php?pid=%d",$product->SupersededBy));
		}
	}
} else {
	redirect("Location: index.php");
}


$product->GetImages();
$product->GetExamples();
$product->GetLinkObjects();
$product->GetDownloads();
$product->GetSpecs();
$product->GetRelatedByType();
$product->GetRelatedByType('Energy Saving Alternative');
$product->GetComponents();
$product->GetReviews();
$product->GetAlternativeCodes();
$product->GetBarcodes();
$product->GetQualityLinksByType('Premium');

$productType = '';

foreach($product->Spec as $spec) {
	if($spec['Name'] == 'Type') {
		$productType = $spec['Value'];
	}
}

$hasCustomerBought = false;

if($session->IsLoggedIn) {
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Product_ID=%d WHERE o.Customer_ID=%d", mysql_real_escape_string($product->ID), mysql_real_escape_string($session->Customer->ID)));
	if($data->Row['count'] > 0) {
		$hasCustomerBought = true;		
	}
	$data->Disconnect();
}


$yt_Videos = new ProductVideo();
$yt_Videos->GetProductVideo($product->ID);

$cookie = new ProductCookie();
$cookie->Add($product->ID);
$cookie->Set();

$category = new Category();
$category->ID = 0;

if(id_param('cat')) {
	$category->Get(id_param('cat'));
	$breadCrumb = new CategoryBreadCrumb();
	$breadCrumb->Get($category->ID, true);
} else if(param('cat') && !id_param('cat')){
	redirect("Location: index.php");
}

if($action == 'favourite') {
	if($session->IsLoggedIn) {
		$customerProduct = new CustomerProduct();
		$customerProduct->Product->ID = $product->ID;
		$customerProduct->Customer->ID = $session->Customer->ID;
		
		if(!$customerProduct->Exists()) {
			$customerProduct->Add();
		}
	}

	redirectTo(sprintf('?pid=%d&cat=%d', $product->ID, $category->ID));
}

if($session->IsLoggedIn) {
	$formReview = new Form($_SERVER['PHP_SELF'] . '#tab-reviews');
	$formReview->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$formReview->AddField('form', 'Form', 'hidden', 'reviews', 'alpha', 7, 7);
	$formReview->SetValue('form', 'reviews');
	$formReview->AddField('tab', 'Tab', 'hidden', 'reviews', 'alpha', 1, 20);
	$formReview->SetValue('tab', 'reviews');
	$formReview->AddField('pid', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$formReview->AddField('cat', 'Category ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$formReview->AddField('title', 'Title', 'text', '', 'anything', 1, 50, true, 'style="width: 50%;"');
	$formReview->AddField('review', 'Review', 'textarea', '', 'anything', 1, 8192, true, 'style="width: 100%;" rows="5"');
	$formReview->AddField('rating', 'Rating', 'select', '', 'anything', 1, 11, true);
	$formReview->AddOption('rating', '', '');

	for($i=1; $i<=$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i++) {
		if($i == 1) {
			$value = sprintf('%d (Lowest)', $i);
		} elseif($i == $GLOBALS['PRODUCT_REVIEW_RATINGS']) {
			$value = sprintf('%d (Highest)', $i);
		} else {
			$value = $i;
		}

		$formReview->AddOption('rating', $i, $value);
	}
	
	if(param('confirm')) {
		if(param('form')) {
			if(param('form') == 'reviews') {
				if($formReview->Validate()) {
					$productReview = new ProductReview();
					$productReview->Product->ID = $product->ID;
					$productReview->Customer->ID = $session->Customer->ID;
					$productReview->Title = $formReview->GetValue('title');
					$productReview->Review = $formReview->GetValue('review');
					$productReview->Rating = max(min(round($formReview->GetValue('rating')), $GLOBALS['PRODUCT_REVIEW_RATINGS']), 1) / $GLOBALS['PRODUCT_REVIEW_RATINGS'];
					$productReview->Add();

					redirectTo(sprintf('?pid=%d&cat=%d&tab=reviews&reviews=thanks#tab-reviews', $product->ID, $category->ID));
				}
			}
		}
	}
}

$formEnquiry = new Form($_SERVER['PHP_SELF'] . '#tab-enquire');
$formEnquiry->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$formEnquiry->AddField('form', 'Form', 'hidden', 'enquire', 'alpha', 7, 7);
$formEnquiry->SetValue('form', 'enquire');
$formEnquiry->AddField('tab', 'Tab', 'hidden', 'enquire', 'alpha', 1, 20);
$formEnquiry->SetValue('tab', 'enquire');
$formEnquiry->AddField('pid', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$formEnquiry->AddField('cat', 'Category ID', 'hidden', '0', 'numeric_unsigned', 1, 11);

if(!$session->IsLoggedIn) {
	$captcha = new Securimage();
	
	$formEnquiry->AddField('title', 'Title', 'select', '', 'anything', 0, 20, false);
	$formEnquiry->AddOption('title', '', '');

	$data = new DataQuery("SELECT Person_Title FROM person_title ORDER BY Person_Title ASC");
	while($data->Row){
		$formEnquiry->AddOption('title', $data->Row['Person_Title'], $data->Row['Person_Title']);
		
		$data->Next();
	}
	$data->Disconnect();

	$formEnquiry->AddField('businessname', 'Business Name', 'text', '', 'anything', 1, 255, false);	
	$formEnquiry->AddField('firstname', 'First Name', 'text', '', 'anything', 1, 60, false);
	$formEnquiry->AddField('lastname', 'Last Name', 'text', '', 'anything', 1, 60, false);
	$formEnquiry->AddField('email', 'Email Address', 'text', '', 'email', null, null);
	$formEnquiry->AddField('phone', 'Phone', 'text', '', 'telephone', null, null);
	$formEnquiry->AddField('code', 'Form Validation Code', 'text', '', 'paragraph', 5, 5);
}

$formEnquiry->AddField('message', 'Message', 'textarea', '', 'paragraph', 1, 16284, true, 'style="width:100%;" rows="10"');

if(strtolower($productType) == 'led') {
	if($session->IsLoggedIn) {
		if($hasCustomerBought) {
			$formExamples = new Form($_SERVER['PHP_SELF'] . '#tab-examples');
			$formExamples->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
			$formExamples->AddField('form', 'Form', 'hidden', 'enquire', 'alpha', 8, 8);
			$formExamples->SetValue('form', 'examples');
			$formExamples->AddField('tab', 'Tab', 'hidden', 'enquire', 'alpha', 1, 20);
			$formExamples->SetValue('tab', 'examples');
			$formExamples->AddField('pid', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
			$formExamples->AddField('cat', 'Category ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
			$formExamples->AddField('image', 'Image', 'file', '', 'file');
		}
	}
}

if(param('confirm')) {
	if(param('form')) {
		if(param('form') == 'enquire') {
			if($formEnquiry->Validate()) {
				if(!$session->IsLoggedIn) {
				    if(!$captcha->check($formEnquiry->GetValue('code'))) {
				    	$formEnquiry->AddError('Sorry, the form validation code entered was incorrect. Please try again.', 'code');
				    }
				}
	    
	    		if($formEnquiry->Valid) {			
					$customerId = $session->Customer->ID;
					
					if(!$session->IsLoggedIn) {
						$customer = new Customer();
						$customerFound = false;
						$emailAddress = trim(strtolower($formEnquiry->GetValue('email')));
						$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Username LIKE '%s'", mysql_real_escape_string($emailAddress)));
						if($data->TotalRows > 0) {
							if($customer->Get($data->Row['Customer_ID'])) {
								$customerFound = true;
							}
						}
						$data->Disconnect();

						if(!$customerFound) {
							$customer->Username = trim(strtolower($formEnquiry->GetValue('email')));
							$customer->Contact->Type = 'I';
							$customer->Contact->IsCustomer = 'Y';
							$customer->Contact->Person->Title = $formEnquiry->GetValue('title');
							$customer->Contact->Person->Name = $formEnquiry->GetValue('firstname');
							$customer->Contact->Person->LastName = $formEnquiry->GetValue('lastname');
							$customer->Contact->Person->Phone1 = $formEnquiry->GetValue('phone');
							$customer->Contact->Person->Email  = $formEnquiry->GetValue('email');
							$customer->Contact->OnMailingList = 'H';
							$customer->Contact->Add();
							$customer->Add();

							if(strlen(trim($formEnquiry->GetValue('businessname'))) > 0) {
								$contact = new Contact();
								$contact->Type = 'O';
								$contact->Organisation->Name = $formEnquiry->GetValue('businessname');
								$contact->Organisation->Type->ID = 0;
								$contact->Organisation->Email = $customer->GetEmail();
								$contact->Add();

								$customer->Contact->Parent->ID = $contact->ID;
								$customer->Contact->Update();
							}
							
							$session->Customer->ID = $customer->ID;
							$session->Update();
						}
						
						$customerId = $customer->ID;
					}
					
					$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type WHERE Developer_Key LIKE 'customerservices'"));
					$enquiryTypeId = $data->Row['Enquiry_Type_ID'];
					$data->Disconnect();

					$enquiry = new Enquiry();
					$enquiry->Type->ID = $enquiryTypeId;
					$enquiry->Customer->ID = $customerId;
					$enquiry->Status = 'Unread';
					$enquiry->Subject = $product->Name;
					$enquiry->Add();

					$enquiryLine = new EnquiryLine();
					$enquiryLine->Enquiry->ID = $enquiry->ID;
					$enquiryLine->IsCustomerMessage = 'Y';
					$enquiryLine->Message = $formEnquiry->GetValue('message');
					$enquiryLine->Add();
		
					redirectTo(sprintf('?pid=%d&cat=%d&tab=enquire&enquire=thanks#tab-enquire', $product->ID, $category->ID));
				}
			}
		} elseif(param('form') == 'examples') {
			if($formExamples->Validate()) {
				$request = new ProductImageExampleRequest();
				$request->product->ID = $product->ID;
				$request->customer->ID = $session->Customer->ID;
				$request->asset->attach('image');
				$request->add();
	
				redirectTo(sprintf('?pid=%d&cat=%d&tab=examples&examples=thanks#tab-examples', $product->ID, $category->ID));
			}
		}
	}
}

$groupsType = array();
$groupsEquivalentWattage = array();
$groupsWattage = array();
$groupsLampLife = array();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE 'type'"));
while($data->Row) {
	$groupsType[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%%equivalent%%' AND Reference LIKE '%%wattage%%'"));
while($data->Row) {
	$groupsEquivalentWattage[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE 'wattage'"));
while($data->Row) {
	$groupsWattage[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%%lamp%%' AND Reference LIKE '%%life%%'"));
while($data->Row) {
	$groupsLampLife[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();

$specEquivalentWattage = null;
$specWattage = null;
$specLampLife = null;

if(!empty($groupsEquivalentWattage)) {
	$data = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsEquivalentWattage), mysql_real_escape_string($product->ID)));
	if($data->TotalRows > 0) {
		$specEquivalentWattage = preg_replace('/[^0-9\.]/', '', $data->Row['Value']);
	}
	$data->Disconnect();
}

if(!empty($groupsWattage)) {
	$data = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsWattage), mysql_real_escape_string($product->ID)));
	if($data->TotalRows > 0) {
		$specWattage = preg_replace('/[^0-9\.]/', '', $data->Row['Value']);
	}
	$data->Disconnect();
}

if(!empty($groupsLampLife)) {
	$data = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ',$groupsLampLife), mysql_real_escape_string($product->ID)));
	if($data->TotalRows > 0) {
		$specLampLife = preg_replace('/[^0-9\.]/', '', $data->Row['Value']);
	}
	$data->Disconnect();
}
include("ui/nav.php");
include("ui/search.php");?>
<!--<script type="text/javascript" src="js/slimbox.js"></script>
<link rel="stylesheet" type="text/css" href="css/jquery.fancybox.css" />
<script type="text/javascript" src="js/fancybox.js"></script>
-->
<script type="text/javascript">
		<?php
		if(!empty($specEquivalentWattage) && !empty($specWattage) && !empty($specLampLife)) {
			?>
			
			function calculateSaving() {
				var savingElement = document.getElementById('energy-saving-total');
				
				var inputQuantity = document.getElementById('energy-saving-input-quantity');
				var inputRate = document.getElementById('energy-saving-input-rate');
				
				var specEquivalentWattage = <?php echo $specEquivalentWattage; ?>;
				var specWattage = <?php echo $specWattage; ?>;
				var specLampLife = <?php echo $specLampLife; ?>;

				if(savingElement && inputQuantity && inputRate) {
					var saving = (specEquivalentWattage - specWattage) * (parseFloat(inputRate.value) / 100 / 1000) * specLampLife * parseInt(inputQuantity.value);

					if(isNaN(saving)) {
						saving = 0;
					}
					
					savingElement.innerHTML = saving.toFixed(2);
				}
			}
		
			<?php
		}
		?>
		
		function showReview() {
			var inputElement = document.getElementById('product-review-input');
			
			if(inputElement) {
				inputElement.style.display = 'block';
			}
			
			var createElement = document.getElementById('product-review-create');
			
			if(createElement) {
				createElement.style.display = 'none';
			}
		}

		function toggleUpgrades() {
			var element = document.getElementById('product-upgrade');
			
			if(element) {
				element.style.display = (element.style.display == 'none') ? 'block' : 'none';
			}
		}

		function closeUpgrades() {
			var element = document.getElementById('product-upgrade');
			
			if(element) {
				element.style.display = 'none';
			}
		}
		
		function setImage(image, title) {
			var imageElement = document.getElementById('product-image-main');
			
			if(imageElement) {
				imageElement.src = image;
				imageElement.alt = title;
			}
		}
		
		addContent('overview');
		addContent('specifications');
		addContent('related');
		addContent('relatedenergysaving');
		addContent('components');
		addContent('reviews');
		addContent('enquire');
		addContent('examples');
	</script>


<!--	<script type="text/javascript">
		jQuery(document).ready(function() {

			jQuery("a[data-video]").click(function(e){
					e.preventDefault();

					jQuery.fancybox({
			            'padding'       : 0,
			            'autoScale'     : false,
			            'transitionIn'  : 'none',
			            'transitionOut' : 'none',
			            'title'			: this.title,
			            'width'         : 300,
			            'height'        : 200,
			            'href'          : this.href.replace(new RegExp("watch\\?v=", "i"), 'v/'),
			            'type'          : 'swf',
			            'swf'           : {
				            'wmode'             : 'transparent',
				            'allowfullscreen'   : 'true'
				            }
	       			 });
				return false;
			});
		});
	</script>-->
    <div class="cartmiddle1"><p style="font-size:16px;color:#333;  vertical-align:middle;"><?php echo $product->Name; ?></p></div>
<div class="maincontent">
<div class="maincontent1">
<?php /*?>              		<h1><?php echo $product->Name; ?></h1>
                    <?php 
					$bc=$breadCrumb->Text;
					$newbc=str_replace('href="/','href="'. $GLOBALS['MOBILE_LINK'] . '/',$bc);
					?>
					<p class="breadcrumb"><a href="./">Home</a> <?php echo isset($breadCrumb) ? $newbc : ''; ?></p>	<?php */?>
					<?php //include('../lib/templates/bought_wspl.php'); ?>
					
					<?php
					if(($product->Discontinued == 'Y') && ($product->DiscontinuedShowPrice == 'Y')) {
						if($product->SupersededBy > 0) {
							$superseded = new Product();
							$superseded->Get($product->SupersededBy);  

							$bubble = new Bubble('Superseded!', sprintf('This product is discontinued and has been superseded by the <strong><a href="?pid=%d">%s</a></strong>.', $superseded->ID, $superseded->Name));
							?>
							
							<div class="attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Item Superseded</span><br />
									This item was discontinued on <?php echo cDatetime($product->DiscontinuedOn, 'longdate'); ?> and has been superseded by <a href="?pid=<?php echo $superseded->ID; ?>"><?php echo $superseded->Name; ?></a>.
									
									<?php
									if(!empty($product->DiscontinuedBecause)) {
										?>
										
										<br /><br />
										<?php echo $product->DiscontinuedBecause; ?>
										
										<?php	
									}
									?>
								</div>
							</div>
							
							<?php
						} else {
							?>
							
							<div class="attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Item Discontinued</span><br />
									This product was discontinued on <?php echo cDatetime($product->DiscontinuedOn, 'longdate'); ?> and is no longer available.
									
									<?php
									if(!empty($product->DiscontinuedBecause)) {
										?>
										
										<br /><br />
										<?php echo $product->DiscontinuedBecause; ?>
										
										<?php	
									}
									?>
								</div>
							</div>
							
							<?php							
						}
					}
					
					if($product->Discontinued == 'N') {
						$isStockWarning = false;
						
						$warnCategoriesStock = array(1634);

						$data = new DataQuery(sprintf("SELECT Category_ID FROM product_in_categories WHERE Product_ID=%d", mysql_real_escape_string($product->ID)));
						while($data->Row) {
							$categories = getCategories($data->Row['Category_ID']);
							
							foreach($warnCategoriesStock as $categoryItem) {
								if(in_array($categoryItem, $categories)) {
									$isStockWarning = true;
								}		
							}
							
							$data->Next();	
						}
						$data->Disconnect();

						if($isStockWarning) {
							?>
							
							<div class="attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Stock Warning</span><br />
									For coloured bulbs please call our sales lines on <?php echo Setting::GetValue('telephone_sales_hotline'); ?> between 8:30 and 17:00. We are currently holding very limited stock - please call to check availability before placing your order.
								</div>
							</div>
							
							<?php
						}

						if($product->IsNonReturnable == 'Y') {
							?>
							
							<div class="attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Non-returnable Item</span><br />
									This product is a special order item that is non-returnable. Please ensure you are ordering the correct product. For further information please call our sales line on <?php echo Setting::GetValue('telephone_sales_hotline'); ?>.
								</div>
							</div>
							
							<?php
						}

						$data = new DataQuery(sprintf("SELECT Backorder_Expected_On FROM warehouse_stock WHERE Product_ID=%d AND Is_Backordered='Y' ORDER BY Backorder_Expected_On ASC LIMIT 0, 1", mysql_real_escape_string($product->ID)));
						if($data->TotalRows > 0) {
							?>
							
							<div class="attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Temporarily Unavailable</span><br />
									This product is out of stock until <?php echo cDatetime($data->Row['Backorder_Expected_On'], 'longdate'); ?>. You are still able to order this product but delivery will be after this date.
								</div>
							</div>
							
							<?php
						}
						$data->Disconnect();
					}
					?>
					
					<div class="product-image">
						<div class="product-image-main">
							<?php
							if(!empty($product->DefaultImage->Large->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Large->FileName)) {
								?>
								
								<img id="product-image-main" src="<?php echo $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$product->DefaultImage->Large->FileName; ?>" alt="<?php echo $product->Name; ?>" align="middle" width="50%" />
								
								<?php
							}
							?>
						</div>
						
<?php /*?>							<?php
						$thumbNails = 0;
						foreach($product->Image as $image) {
							if(file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$image->Thumb->FileName)) {
								$thumbNails++;
							}	
						}
						
						foreach($product->Example as $image) {
							if(file_exists($GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_FS'].$image->Thumb->FileName)) {
								$thumbNails++;
							}	
						}
						
					if($thumbNails > 1) {
							?>
							<div class="product-image-thumb">
								<?php
								foreach($product->Image as $image) {
									if(file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$image->Thumb->FileName)) {
										$image->Thumb->Width = round($image->Thumb->Width / 2);
										$image->Thumb->Height = round($image->Thumb->Height / 2);
										?>
										<div class="product-image-thumb-item" >
											<img src="<?php echo $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$image->Thumb->FileName; ?>" alt="<?php echo $image->Name; ?>" width="<?php echo $image->Thumb->Width; ?>" height="<?php echo $image->Thumb->Height; ?>" onmouseover="setImage('<?php echo $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$image->Large->FileName; ?>', '<?php echo $image->Name; ?>');" />
										</div>
										
										<?php
									}
								}
								
								foreach($product->Example as $image) {
									if(file_exists($GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_FS'].$image->Thumb->FileName)) {
										$image->Thumb->Width = round($image->Thumb->Width / 2);
										$image->Thumb->Height = round($image->Thumb->Height / 2);
										?>
										
										<div class="product-image-thumb-item product-image-thumb-item-example">
											<a href="<?php echo $GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_WS'].$image->Large->FileName; ?>" title="Click to expand" rel="lightbox">
												<img src="<?php echo $GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_WS'].$image->Thumb->FileName; ?>" alt="<?php echo $image->Name; ?>" width="<?php echo $image->Thumb->Width; ?>" height="<?php echo $image->Thumb->Height; ?>" onclick="setExample('<?php echo $GLOBALS['PRODUCT_EXAMPLE_IMAGES_DIR_WS'].$image->Large->FileName; ?>', '<?php echo $image->Name; ?>');" />
											</a>
										</div>
										
										<?php
									}
								}
								?>
							</div>
							
							<?php
						}?><?php */?>
					</div>
					
					
					<?php
					if(($product->Discontinued == 'Y') && ($product->DiscontinuedShowPrice == 'N')) {
						if($product->SupersededBy > 0) {
							$superseded = new Product();
							$superseded->Get($product->SupersededBy);  

							$bubble = new Bubble('Superseded!', sprintf('This product is discontinued and has been superseded by the <strong><a href="?pid=%d">%s</a></strong>.', $superseded->ID, $superseded->Name));
							?>
							
							<div class="attention product-attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Item Superseded</span><br />
									This item was discontinued on <?php echo cDatetime($product->DiscontinuedOn, 'longdate'); ?> and has been superseded by <a href="?pid=<?php echo $superseded->ID; ?>"><?php echo $superseded->Name; ?></a>.
									
									<?php
									if(!empty($product->DiscontinuedBecause)) {
										?>
										
										<br /><br />
										<?php echo $product->DiscontinuedBecause; ?>
										
										<?php	
									}
									?>
								</div>
							</div>
							
							<?php
						} else {
							?>
							
							<div class="attention product-attention">
								<div class="attention-icon attention-icon-warning"></div>
								<div class="attention-info attention-info-warning">
									<span class="attention-info-title">Item Discontinued</span><br />
									This product was discontinued on <?php echo cDatetime($product->DiscontinuedOn, 'longdate'); ?> and is no longer available.
									
									<?php
									if(!empty($product->DiscontinuedBecause)) {
										?>
										
										<br /><br />
										<?php echo $product->DiscontinuedBecause; ?>
										
										<?php	
									}
									?>
								</div>
							</div>
							
							<?php							
						}
					} else {
						?>
						
						<div class="product-buy">

							<?php /*?><?php
							if(!empty($product->Quality)) {
								?>
								
								<div class="product-quality product-quality-<?php echo strtolower($product->Quality); ?>" id="product-quality">
								
									<?php
									if(($product->Quality == 'Value') && !empty($product->QualityLinkType['Premium'])) {
										?>

										<div class="product-quality-upgrade">
											<a href="javascript:toggleUpgrades();">Upgrade</a>
										</div>

										<?php
									}
									?>

									<div class="product-quality-type"><?php echo $product->Quality; ?> Range</div>
									<div class="clear"></div>
								</div>


								<?php
								if(!empty($product->QualityLinkType['Premium'])) {
									?>

									<div class="product-quality-options">

										<div class="product-upgrade" id="product-upgrade" style="display: none;">
											<div class="product-upgrade-arrow">
												<div class="product-upgrade-arrow-image"></div>
											</div>
											<div class="product-upgrade-box">
												<a class="product-upgrade-close" href="javascript:closeUpgrades();"></a>
												<div class="product-upgrade-title">
													<h2>Premium Range</h2>
													Upgrade this product to...
												</div>
												
												<div class="product-upgrade-product">

													<?php
													if(!empty($product->QualityText)) {
														?>

														<div class="product-upgrade-product-quality"><?php echo $product->QualityText; ?></div>

														<?php
													}

													foreach($product->QualityLinkType['Premium'] as $quality) {
														$subProduct = new Product();

														if($subProduct->Get($quality['Product_ID'])) {
															$cartDirect = 'product.php?pid=' . $subProduct->ID;
															$analyticsTag = 'upgrade';

															include('../lib/templates/productPanel_wspl.php');

															unset($analyticsTag);
															unset($cartDirect);

															break;
														}
													}
													?>

													<div class="clear"></div>
												</div>
											</div>
										</div>

									</div>

									<?php
								}
							}
							?><?php */?>							
						
							<div class="product-price">
								<div class="product-price-sale">
									<div class="product-price-sale-icon"></div>
								</div>
								
								<?php
				  				$shownCustomPrice = false;
					
								if($session->IsLoggedIn) {
									if($session->Customer->Contact->IsTradeAccount == 'N') {
										if(count($discountCollection->Line) > 0){
											list($discountAmount, $discountName) = $discountCollection->DiscountProduct($product, 1);

											if($discountAmount < $product->PriceCurrent)  {
				  								$shownCustomPrice = true;
				  								
				  								$product->PriceCurrent = $discountAmount;
				  								
				  								$product->PriceCurrentIncTax = $product->PriceCurrent + $globalTaxCalculator->GetTax($discountAmount, $product->TaxClass->ID);
				  								$product->PriceCurrentIncTax = round($product->PriceCurrentIncTax, 2);
											}
										}
									}
								}

								if(!$shownCustomPrice) {
									if($session->Customer->Contact->IsTradeAccount == 'Y') {
										$retailPrice = $product->PriceCurrent;
										$tradeCost = ($product->CacheRecentCost > 0) ? $product->CacheRecentCost : $product->CacheBestCost;
										
										$product->PriceOurs = ContactProductTrade::getPrice($session->Customer->Contact->ID, $product->ID);
										$product->PriceOurs = ($product->PriceOurs <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $product->ID) / 100) + 1) : $product->PriceOurs;
										
										$product->PriceCurrent = $product->PriceOurs;
										
										$product->PriceCurrentIncTax = $product->PriceCurrent + $globalTaxCalculator->GetTax($product->PriceCurrent, $product->TaxClass->ID);
										$product->PriceCurrentIncTax = round($product->PriceCurrentIncTax, 2);
			
										$product->PriceSaving = $retailPrice - $product->PriceCurrent;
										$product->PriceSavingPercent = round(($product->PriceSaving / $retailPrice) * 100);
									}
								}

								if($session->Customer->Contact->IsTradeAccount == 'N') {
									if($product->PriceRRP > 0) {
										?>
										
										<div class="product-price-amount">
											<div class="product-price-amount-text">
												<strong>RRP</strong><br />
												<span class="product-price-amount-old colour-blue">&pound;<?php echo number_format($product->PriceRRP, 2, '.', ','); ?></span>
											</div>
										</div>
										
										<?php
				  					}
				  				
									if($product->PriceOurs < $product->PriceCurrent) {
										?>
										
										<div class="product-price-amount">
											<div class="product-price-amount-text">
												<strong>Was</strong><br />
												<span class="product-price-amount-old colour-blue">&pound;<?php echo number_format($product->PriceOurs, 2, '.', ','); ?></span>
											</div>
										</div>
										
										<?php
				  					}
				  					?>
				  					
				  					<div class="product-price-amount">
										<div class="product-price-amount-text">
											<strong>Price</strong><br />
											<span class="product-price-amount-current colour-red"><strong>&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?></strong></span>
										</div>
									</div>
									
									<?php
								} else {
									?>
										
									<div class="product-price-amount">
										<div class="product-price-amount-text">
											<strong>Retail</strong><br />
											<span class="product-price-amount-old colour-blue">&pound;<?php echo number_format($retailPrice, 2, '.', ','); ?></span>
										</div>
									</div>
									<div class="product-price-amount">
										<div class="product-price-amount-text">
											<strong>Trade</strong><br />
											<span class="product-price-amount-current colour-red"><strong>&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?></strong></span>
										</div>
									</div>
									
									<?php
								}
				  				?>
								
								<div class="product-price-amount">
									<div class="product-price-amount-text">
										<strong>Inc. VAT</strong><br />
										<span class="product-price-amount-current colour-grey">&pound;<?php echo number_format($product->PriceCurrentIncTax, 2, '.', ','); ?></span>
									</div>
								</div>
								<div class="product-price-saving">
									<div class="product-price-saving-text">
										<strong>SAVE</strong><br />
										<span class="product-price-saving-percent"><?php echo $product->PriceSavingPercent; ?>%</span>
									</div>
								</div>
							</div>
                                                        
                            <div class="product-bar">
						<table width="100%">
							<tr>
								<td class="product-bar-item product-bar-item-ident" width="60%">
									<div>
										<div class="product-bar-item-data">Quickfind</div>
										<strong class="colour-red text-large"><?php echo $product->ID; ?></strong>
									</div>
									<div>
										<div class="product-bar-item-data">Part No.</div>
										<strong class="colour-black"><?php echo $product->SKU; ?></strong>
									</div>
								</td>
                                <td width="40%">                                
                                <div class="product-button">							
								<div class="product-button-buy">
									<form action="customise.php" method="post" name="buy" id="buy">
										<input type="hidden" name="action" value="customise" />
										<input type="hidden" name="direct" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" />
										<input type="hidden" name="product" value="<?php echo $product->ID; ?>" />
										<input type="hidden" name="category" value="<?php echo $category->ID; ?>" />
										
										<?php /*?><div class="product-button-buy-field">
											<input type="text" name="quantity" value="<?php echo ($product->OrderMin > 0) ? $product->OrderMin : 1; ?>" size="3" maxlength="4" class="product-button-buy-field-text" /><?php */?>
											<input type="image" name="buy" alt="Buy" src="images/new/product/buy.png" />
<!--										</div>
-->									</form>
								</div>
								
<?php /*?>								<?php
								if($session->IsLoggedIn) {
									$customerProduct = new CustomerProduct();
									$customerProduct->Product->ID = $product->ID;
									$customerProduct->Customer->ID = $session->Customer->ID;
									
									if(!$customerProduct->Exists()) {
										?>

										<div class="product-button-favourite">
											<a href="?action=favourite&amp;pid=<?php echo $product->ID; ?>&amp;cat=<?php echo $category->ID; ?>"><img src="images/new/product/favourite.png" width="137" height="25" alt="Favourite Bulb" /></a>
										</div>
										
										<?php
									}
								}
								?><?php */?>
							</div>
                                </td>
                                </tr>
								
<?php /*?>								<?php
								if($session->Customer->Contact->IsTradeAccount == 'Y') {
									$stockData = new DataQuery(sprintf("SELECT SUM(ws.Quantity_In_Stock) AS Stock FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($product->ID)));
									if($stockData->TotalRows > 0) {
										if($stockData->Row['Stock'] > 0) {
											?>
											<tr>
											<td class="product-bar-item product-bar-item-stock">
												<strong class="colour-black">In Stock <span class="colour-orange"><?php echo $stockData->Row['Stock']; ?></span></strong><br />
												Available for delivery
											</td>
										</tr>
											<?php
										}
									}
									$stockData->Disconnect();
								}

								if($product->ShippingClass->ID == 45) {
									?>
									<tr>
									<td class="product-bar-item product-bar-item-shipping">
										<a href="deliveryRates.php">
											<strong class="colour-black"><span class="colour-red">FREE</span> Shipping Available</strong><br />
											On orders over &pound;45.00
										</a>
									</td>
                                    </tr>
									
									<?php
								} elseif($product->ShippingClass->ID == 64) {
									?>
									<tr>
									<td class="product-bar-item product-bar-item-coloured">
										<strong class="colour-black">Customise Product</strong><br />
										Lamps coloured to order
									</td>
								</tr>
									<?php
								}
								?>
								<tr>
								<td class="product-bar-item product-bar-item-accounts">
									<a href="creditAccount.php">
										<strong class="colour-blue">Corporate Accounts</strong><br />
										Credit accounts available
									</a>
								</td>                                
							</tr><?php */?>
						</table>
					</div>
                    
                    <div class="product-stars">
								<div class="product-stars-items">
									<?php
									$rating = $product->ReviewAverage;
									$ratingStars = number_format($rating * $GLOBALS['PRODUCT_REVIEW_RATINGS'], 1, '.', '');
									$ratingHtml = '';
			
									for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i++) {
										$ratingHtml .= sprintf('<img src="/images/new/product/star%s.png" alt="Product Rating" />', (ceil($ratingStars) > $i) ? '-solid' : '');
									}
									
									echo sprintf('<a href="#tab-reviews" onclick="setContent(\'reviews\');" title="Reviews for %s">%s</a>', $product->Name, $ratingHtml);
									?>
								</div>
								<div class="product-stars-text"><?php echo count($product->Review); ?> customer reviews</div>
								<div class="clear"></div>
							</div>																
						
							<?php
							if(count($product->LinkObject) > 0) {
								?>
								
<?php /*?>								<div class="product-similar">
									<p><strong><?php echo !empty($product->SimilarText) ? $product->SimilarText : 'Not what you were looking for? Try these.'; ?></strong></p>
								
									<?php
									foreach($product->LinkObject as $link) {
										$link->image->Directory = $GLOBALS['CACHE_DIR_FS'];
										$link->image->FileName = $link->asset->hash;
										$link->image->GetDimensions();
										$link->image->Width /= 1.5;
										$link->image->Height /= 1.5;
										?>
										
										<div class="product-similar-item">
											<div class="product-similar-item-image">
												<a href="<?php echo htmlspecialchars($link->url); ?>"><img src="asset.php?hash=<?php echo $link->asset->hash; ?>" alt="<?php echo $link->name; ?>" width="<?php echo $link->image->Width; ?>" height="<?php echo number_format($link->image->Height); ?>" /></a>
											</div>
											
											<a href="<?php echo htmlspecialchars($link->url); ?>"><?php echo stripslashes($link->name); ?></a>
										</div>
										
										<?php
									}
									?><?php */?>
									
								</div>
                             <?php
							}
							?>	
						<div class="clear"></div>
						<?php
					}
					?>
					
	<?php /*?>				<div class="product-bar">
						<table width="100%">
							<tr>
								<td class="product-bar-item product-bar-item-ident">
									<div>
										<div class="product-bar-item-data">Quickfind</div>
										<strong class="colour-red text-large"><?php echo $product->ID; ?></strong>
										<div class="clear"></div>
									</div>
									<div>
										<div class="product-bar-item-data">Part No.</div>
										<strong class="colour-black"><?php echo $product->SKU; ?></strong>
										<div class="clear"></div>
									</div>
								</td></tr>
								
								<?php
								if($session->Customer->Contact->IsTradeAccount == 'Y') {
									$stockData = new DataQuery(sprintf("SELECT SUM(ws.Quantity_In_Stock) AS Stock FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($product->ID)));
									if($stockData->TotalRows > 0) {
										if($stockData->Row['Stock'] > 0) {
											?>
											<tr>
											<td class="product-bar-item product-bar-item-stock">
												<strong class="colour-black">In Stock <span class="colour-orange"><?php echo $stockData->Row['Stock']; ?></span></strong><br />
												Available for delivery
											</td>
										</tr>
											<?php
										}
									}
									$stockData->Disconnect();
								}

								if($product->ShippingClass->ID == 45) {
									?>
									<tr>
									<td class="product-bar-item product-bar-item-shipping">
										<a href="deliveryRates.php">
											<strong class="colour-black"><span class="colour-red">FREE</span> Shipping Available</strong><br />
											On orders over &pound;45.00
										</a>
									</td>
                                    </tr>
									
									<?php
								} elseif($product->ShippingClass->ID == 64) {
									?>
									<tr>
									<td class="product-bar-item product-bar-item-coloured">
										<strong class="colour-black">Customise Product</strong><br />
										Lamps coloured to order
									</td>
								</tr>
									<?php
								}
								?>
								<tr>
								<td class="product-bar-item product-bar-item-accounts">
									<a href="creditAccount.php">
										<strong class="colour-blue">Corporate Accounts</strong><br />
										Credit accounts available
									</a>
								</td>                                
							</tr>
						</table>
					</div><?php */?>
					
					<?php /*?><div class="tab-bar">
						<div class="tab-bar-item <?php echo ($tab == 'overview') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-overview" onclick="setContent('overview');">
							<a href="javascript: void(0);">Overview</a><br />
							<span class="tab-bar-item-sub">product details</span>
						</div>
						
						<?php
						if(count($product->Spec)) {
							?>
							
							<div class="tab-bar-item <?php echo ($tab == 'specifications') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-specifications" onclick="setContent('specifications');">
								<a href="javascript: void(0);">Specifications</a><br />
								<span class="tab-bar-item-sub">technical information</span>
							</div>
						
							<?php
						}
						
						if(count($product->RelatedType[''])) {
							?>
							
							<div class="tab-bar-item <?php echo ($tab == 'related') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-related" onclick="setContent('related');">
								<a href="javascript: void(0);">Related</a><br />
								<span class="tab-bar-item-sub"><?php echo count($product->RelatedType['']); ?> related items</span>
							</div>
							
							<?php
						}
						
						if(count($product->RelatedType['Energy Saving Alternative'])) {
							?>
							
							<div class="tab-bar-item <?php echo ($tab == 'relatedenergysaving') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-relatedenergysaving" onclick="setContent('relatedenergysaving');">
								<a href="javascript: void(0);">Energy Saving</a><br />
								<span class="tab-bar-item-sub"><?php echo count($product->RelatedType['Energy Saving Alternative']); ?> alternatives</span>
							</div>
							
							<?php
						}

						if(count($product->Component)) {
							?>
							
							<div class="tab-bar-item <?php echo ($tab == 'components') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-components" onclick="setContent('components');">
								<a href="javascript: void(0);">Components</a><br />
								<span class="tab-bar-item-sub">includes <?php echo count($product->Component); ?> products</span>
							</div>
							
							<?php
						}
						?>
						
						<div class="tab-bar-item <?php echo ($tab == 'reviews') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-reviews" onclick="setContent('reviews');">
							<a href="javascript: void(0);">Reviews</a><br />
							<span class="tab-bar-item-sub"><?php echo count($product->Review); ?> customer reviews</span>
						</div>
						
						<div class="tab-bar-item <?php echo ($tab == 'enquire') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-enquire" onclick="setContent('enquire');">
							<a href="javascript: void(0);">Enquire</a><br />
							<span class="tab-bar-item-sub">product enquiry</span>
						</div>
						
						<?php
						if(strtolower($productType) == 'led') {
							if($session->IsLoggedIn) {
								if($hasCustomerBought) {
									?>
									
									<div class="tab-bar-item <?php echo ($tab == 'examples') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-examples" onclick="setContent('examples');">
										<a href="javascript: void(0);">Examples</a><br />
										<span class="tab-bar-item-sub">submit example</span>
									</div>
									
									<?php
								}
							}
						}
						?>
						
						<div class="clear"></div>
					</div><?php */?>
					
					<div class="tab-content">
                    <div id="menubody">
                    <div id="menubodytitles" onclick="setContent('overview');"><div id="menutitles"><a class="WhiteLnkSideMenu" title="OverView"><center>OverView</center></a></div></div>
					<div class="tab-content-item" id="tab-content-item-overview" <?php echo ($tab == 'overview') ? '' : 'style="display: none;"'; ?>>
<!--						<div class="tab-content-item" id="tab-content-item-overview">
-->							<div class="tab-content-side">
								<div class="tab-content-title">
									<h2>Product Summary</h2>
									this product at a glance
								</div>								
								<div class="product-basic-specification">
									<?php
									$specColumns = 2;
									$specIndex = 0;
									$specCount = 0;
									
									$data = new DataQuery(sprintf("SELECT psg.Name, psg.Reference, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification AS ps LEFT JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID LEFT JOIN product_specification_group AS psg ON psv.Group_ID=psg.Group_ID AND psg.Is_Hidden='N' WHERE ps.Product_ID=%d AND psg.Is_Visible='Y' AND ps.Is_Primary='Y' ORDER BY psg.Name ASC", mysql_real_escape_string($product->ID)));
									if($data->TotalRows > 0) {
										?>
										
										<table class="list list-thin list-border">
										
											<?php
											while($data->Row) {
												if($specIndex == 0) {
													echo '<tr>';
												}
												//<tr>
												?>

												
												  <td class="list-image product-basic-specification-image">
												
													<?php
													$fileName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data->Row['Reference']));

													if($fileName == 'rating') {
														$fileName .= '-' . strtolower($data->Row['Value']);
													}
													
													if(file_exists($GLOBALS['DIR_WS_ROOT'] . './images/new/specification/' . $fileName . '.png')) {
														?>
													
														<img src="./images/new/specification/<?php echo $fileName; ?>.png" alt="<?php echo $data->Row['Name']; ?>" />
													
														<?php
													}
													?>
													
												</td>
												<td class="product-basic-specification-text">
													<strong><?php echo str_replace('/', ' / ', $data->Row['UnitValue']); ?></strong><br />
													<span class="colour-grey"><?php echo str_replace('/', ' / ', $data->Row['Name']);
													//echo $specIndex ." ".$specCount;
													 ?></span>
												</td>

												<?php
												$specIndex++;
												$specCount++;
												$data->Next();
												if(($specCount & 1 == 1) && ($specCount == $data->TotalRows)){
													echo '<td>&nbsp;</td><td>&nbsp;</td>';
												}

												if(($specIndex == $specColumns) || ($specCount == $data->TotalRows)) {
													$specIndex = 0;
													
													echo '</tr>';
												}			
											}// </tr>
											?>
											
								 
								  </table>
										
										<?php
									}
									$data->Disconnect();
									?>

								</div>
								
								<div class="bullets">
									<ul>
										<li><a href="javascript:void(0);" onclick="setContent('specifications');">Full Specification</a></li>
										
										<?php
										if(count($product->AlternativeCode) > 0) {
											?>
											
											<li><a href="javascript:void(0);" onclick="setContent('specifications');">Alternative Part Codes</a></li>
											
											<?php
										}
										?>
										
									</ul>
								</div>
								
							</div>
							
							<div class="tab-content-guttering">
								<div class="tab-content-title">
									<h2 style="width:100%;"><?php echo $product->Name; ?></h2>
									<?php
									if($product->Manufacturer->ID > 0) {
										echo sprintf('from %1$s ', $product->Manufacturer->Name);
									}
									
									if(!empty($product->Model)) {
										echo sprintf('(Model: %1$s)', $product->Model);
									}
									?>
								</div>
								
								<?php
								if(!empty($specEquivalentWattage) && !empty($specWattage) && !empty($specLampLife)) {
									$saving = ($specEquivalentWattage - $specWattage) * (12 / 100 / 1000) * $specLampLife;
									?>
									
									<div class="product-saving">
										<div class="attention">
											<div class="attention-info attention-info-general">
												<span class="attention-info-title">Energy Savings</span><br />
												Potential saving for <input class="text" type="text" value="1" size="3" maxlength="3" onkeyup="calculateSaving();" id="energy-saving-input-quantity" /> bulb over its manufacturers predicted life: <strong>&pound;<span id="energy-saving-total"><?php echo number_format($saving, 2, '.', ','); ?></span></strong><br /><br />
												Assuming electricity is charged at <span style="nowrap"><input class="text" type="text" value="12" size="4" maxlength="4" onkeyup="calculateSaving();" id="energy-saving-input-rate" />p</span> per kWh rate (most electricity companies charge approx. 12p per kWh).
											</div>
										</div>
									</div>
									
									<?php
								}
								
							if(isHtml($product->Description)) {?>
                                <table class="form" width="100%"><tr>
									<td><?php echo $product->Description;?></td>                                    
								<?php } else {?>                                
									<td><?php echo sprintf('%s', nl2br($product->Description));?></td>
								<?php }?>
                                </tr></table>
								<?php if(count($product->Download) > 0) {
									?>
									
									<div class="tab-content-section">
									
										<div class="tab-content-title">
											<h3>Product Downloads</h3>
											available for this item
										</div>
										
										<?php
										if(!empty($product->Download)) {
											?>
											
											<table class="list">

												<?php
												foreach($product->Download as $download) {
													?>

													<tr>
														<td class="list-image" style="width:1%">
															<?php
															$items = explode('.', $download->file->FileName);
															
															$fileExtension = $items[count($items) - 1];
															$fileImage = 'images/icons/mimetypes/' . $fileExtension . '.png';

															if(file_exists($GLOBALS['DIR_WS_ROOT'] . $fileImage)) {
																echo sprintf('<a href="%s%s" target="_blank"><img src="%s" alt="%s" /></a>', $GLOBALS['PRODUCT_DOWNLOAD_DIR_WS'], $download->file->FileName, $fileImage, $download->name);
															}
															?>
														</td>
														<td>
															<a href="<?php echo $GLOBALS['PRODUCT_DOWNLOAD_DIR_WS'].$download->file->FileName; ?>" target="_blank"><?php echo $download->name; ?></a><br />
															<?php echo $download->description; ?>
														</td>
													</tr>

													<?php
												}
												?>

											</table>
											
											<?php
										}
										?>
										
									</div>
									
									<?php
								}
								
								if(count($product->Barcode) > 0) {
									?>
									
									<div class="tab-content-section">
									
										<div class="tab-content-title">
											<h3>Product Barcodes</h3>
											associated with this item
										</div>
										<br />
										
										<?php
										if(!empty($product->Barcode)) {
											?>
											
											<table class="list list-thin list-border-vertical">
												<?php
												foreach($product->Barcode as $barcode) {
													?>

													<tr>
														<td class="list-image" style="width:1%"><img src="images/icons/barcode.png" alt="Barcode" /></td>
														<td><?php echo $barcode['Barcode']; ?></td>
													</tr>
														
													<?php
												}
												?>
											</table>

											<?php
										}
										?>
										
									</div>
									
									<?php
								}
								?>
								
								<div class="tab-content-section">
									<div class="tab-content-title">
										<h3>Additional Information</h3>
										for product items in general
									</div>
							
									<div class="bullets">
										<ul>
											<li><a href="./deliveryRates.php">Delivery Information</a></li>
											<li><a href="./lampBaseExamples.php">Lamp Base Example</a></li>
											<li><a href="./energy-saving-bulbs.php">Energy Saving Comparisons</a></li>
											<li><a href="./beamangles.php">Beam Angles</a></li>
											<li><a href="./lampColourTemperatures.php">Colour Temperature Chart</a></li>
										</ul>
									</div>
								</div>
							</div>
							<div class="clear"></div>
								
						</div>
                        <?php
						if(count($product->Spec) > 0) {
							?>
						<div id="menubodytitles" onclick="setContent('specifications');"><div id="menutitles"><a class="WhiteLnkSideMenu" title="Specification" href="#tab-content-item-specifications"><center>Specification</center></a></div></div>
						<div class="tab-content-item" id="tab-content-item-specifications" <?php echo ($tab == 'specifications') ? '' : 'style="display: none;"'; ?>>
<!--                        <div class="tab-content-item" id="tab-content-item-specifications">
-->							
							<?php
							if(count($product->AlternativeCode) > 0) {
								?>
								
								<div class="tab-content-side">
									<div class="tab-content-title">
										<h2>Part Codes</h2>
										of alternative stock identifiers
									</div>
									
									<?php
									if(!empty($product->AlternativeCode)) {
										?>
											
										<table class="list list-thin list-border">

											<?php
											foreach($product->AlternativeCode as $code) {
												?>

												<tr>
													<td><?php echo $code['Code']; ?></td>
												</tr>

												<?php
											}
											?>

										</table>
										
										<?php
									}
									?>
									
								</div>
										
								<?php
							}
							?>
								<div class="tab-content-guttering">
							
																
							<div class="tab-content-title">
								<a id="tab-specifications"></a>
								<h2>Technical Specifications</h2>
								for <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(!empty($product->Spec)) {
								?>
										
								<table class="list list-thin list-border-vertical">

									<?php
									$columns = array();
									$columnsMax = 1;
									$columnIndex = 0;
									$rowIndex = 0;
									
									foreach($product->Spec as $spec) {
										if($rowIndex >= (count($product->Spec) / $columnsMax)) {
											$columnIndex++;
											$rowIndex = 0;
										}

										$columns[$columnIndex][] = $spec;
										$rowIndex++;
									}
									
									for($j=0; $j<count($columns[0]); $j++) {
										?>

										<tr>

											<?php
											for($k=0; $k<count($columns); $k++) {
												if(isset($columns[$k][$j])) {
													?>
												
													<td style="width:<?php echo 50 / $columnsMax; ?>%"><?php echo $columns[$k][$j]['Name']; ?></td>
													<td style="width:<?php echo 50 / $columnsMax; ?>%" class="list-heavy"><?php echo $columns[$k][$j]['UnitValue']; ?></td>
												
													<?php
												} else {
													?>
													
													<td></td><td></td>
													
													<?php
												}
											}
											?>

										</tr>

										<?php
									}
									?>

								</table>
							</div>						
						
						</div>
						<?php	} }
						if(count($product->RelatedType[''])>0) {
							?>
  						<div id="menubodytitles" onclick="setContent('related');"><div id="menutitles"><a class="WhiteLnkSideMenu" title="Related" href="#tab-content-item-related"><center>Related</center></a></div></div>
						<div class="tab-content-item" id="tab-content-item-related" <?php echo ($tab == 'related') ? '' : 'style="display: none;"'; ?>>
<!--                        <div class="tab-content-item" id="tab-content-item-related">
-->							<div class="tab-content-title">
								<a id="tab-related"></a>
								<h2>Products Related</h2>
								to <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(!empty($product->RelatedType[''])) {
								?>
								
								<table class="list">

									<?php
									foreach($product->RelatedType[''] as $related) {
										$subProduct = new Product();
										$subCategory = $category;

										if($subProduct->Get($related['Product_ID'])) {
											include('../lib/templates/productLine_wspl.php');
										}
									}
									?>

								</table>
								
								<?php
							}
							?>
							
						</div>
						<?php }
						if(count($product->RelatedType['Energy Saving Alternative'])) {
							?>
                          		<div id="menubodytitles" onclick="setContent('relatedenergysaving');"><div id="menutitles"><a class="WhiteLnkSideMenu" title="Related Energy Saving" href="#tab-content-item-relatedenergysaving"><center>Related Energy Saving</center></a></div></div>
						<div class="tab-content-item" id="tab-content-item-relatedenergysaving" <?php echo ($tab == 'relatedenergysaving') ? '' : 'style="display: none;"'; ?>>
<!--                		<div class="tab-content-item" id="tab-content-item-relatedenergysaving">
-->
							<div class="tab-content-title">
								<a id="tab-relatedenergysaving"></a>
								<h2>Energy Saving Alternatives</h2>
								for <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(!empty($product->RelatedType['Energy Saving Alternative'])) {
								?>
								
								<table class="list">

									<?php
									$hideSavings = false;
									
									foreach($product->RelatedType['Energy Saving Alternative'] as $related) {
										$subProduct = new Product();
										$subCategory = $category;

										if($subProduct->Get($related['Product_ID'])) {
											include('../lib/templates/productLine_wspl.php');
										}
									}

									unset($hideSavings);
									?>

								</table>
								
								<?php
							}
							?>
							
						</div>
                        <?php	}
						if(count($product->Component)>0) {
							?>
                          		<?php /*?><div id="menubodytitles" onclick="setContent('components');"><div id="menutitles"><a class="WhiteLnkSideMenu" title="Components" href="#tab-content-item-components"><center>Components</center></a></div></div><?php */?>
						<?php /*?><div class="tab-content-item" id="tab-content-item-components" <?php echo ($tab == 'components') ? '' : 'style="display: none;"'; ?>>
<!--						<div class="tab-content-item" id="tab-content-item-components">
-->								<div class="tab-content-title">
								<a id="tab-components"></a>
								<h2>Product Components</h2>
								of <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(!empty($product->Component)) {
								?>
							
								<table class="list">

									<?php
									foreach($product->Component as $component) {
										$subProduct = new Product();
										$subCategory = $category;

										if($subProduct->Get($component['Product_ID'])) {
											$componentQuantity = $component['Component_Quantity'];
											
											include('../lib/templates/productLine_wspl.php');

											unset($componentQuantity);
										}
									}
									?>

								</table>
								
								<?php
							}
							?></div><?php */?>
						<?php
						}
						?>
						
                          		<div id="menubodytitles" onclick="setContent('reviews');"><div id="menutitles"><a class="WhiteLnkSideMenu" title="Reviews" href="#tab-content-item-reviews"><center>Reviews</center></a></div></div>
						<div class="tab-content-item" id="tab-content-item-reviews" <?php echo ($tab == 'reviews') ? '' : 'style="display: none;"'; ?>>
<!--								<div class="tab-content-item" id="tab-content-item-reviews">
-->							<div class="tab-content-side">
								<div class="tab-content-title">
									<h2><?php echo count($product->Review); ?> Reviews</h2>
									submitted by customers
								</div>
								
								<div class="product-review-overview">
									<table class="list list-thin list-border">
										
										<?php
										for($i=$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i>0; $i--) {
											$ratingStars = '';
											$ratingFrequency = 0;
											
											for($j=0; $j<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $j++) {
												$ratingStars .= sprintf('<img src="images/new/product/rating%s.png" alt="Product Rating" />', (ceil($i) > $j) ? '-solid' : '');
											}
											
											for($j=0; $j<count($product->Review); $j++) {
												if(($product->Review[$j]['Rating'] * $GLOBALS['PRODUCT_REVIEW_RATINGS']) == $i) {
													$ratingFrequency++;
												}
											}
											?>

											<tr>
												<td class="product-review-overview-star"><?php echo $ratingStars; ?></td>
												<td class="product-review-overview-extent">
													<div class="product-review-overview-extent-percent">
														
														<?php
														if($ratingFrequency > 0) {
															$ratingWidth = ($ratingFrequency / count($product->Review)) * 100;
															
															echo sprintf('<div class="product-review-overview-extent-percent-ratio" style="width: %s%%;"></div>', $ratingWidth);
														}
														?>
														
													</div>
												</td>
												<td class="product-review-overview-frequency">(<?php echo $ratingFrequency; ?>)</td>
											</tr>

											<?php

										}
										?>

									</table>
								</div>
								
								<?php
								if(strtolower($productType) == 'led') {
									if($session->IsLoggedIn) {
										if($hasCustomerBought) {
											?>
									
											<div class="bullets">
												<ul>
													<li><a href="javascript:void(0);" onclick="setContent('examples');"><center>Submit your examples</center></a></li>
												</ul>
											</div>
											
											<?php
										}
									}
								}
								?>
								
							</div>
							
							<div class="tab-content-guttering">		
								<div class="tab-content-title">
									<a id="tab-reviews"></a>
									<h2>Customer Reviews</h2>
									for <?php echo $product->Name; ?>
									
									<?php
									if(!empty($product->Review)) {
										?>
										
										<div class="product-review-summary">
											<p>Average Customer Review</p>
											
											<div class="product-stars">
												<?php
												$rating = $product->ReviewAverage;
												$ratingStars = number_format($rating * $GLOBALS['PRODUCT_REVIEW_RATINGS'], 1, '.', '');

												for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i++) {
													?>
													
													<div class="product-stars-item <?php echo (ceil($ratingStars) > $i) ? 'product-stars-item-solid' : ''; ?>"></div>
													
													<?php
												}
												?>
												
												<div class="product-stars-score"><?php echo round($ratingStars) . '/' . $GLOBALS['PRODUCT_REVIEW_RATINGS']; ?></div>
												<div class="clear"></div>
											</div>
										</div>
									
										<?php
									}
									
									if(!$session->IsLoggedIn) {
										?>
										
										<div class="product-review-create">
											<input type="button" name="create" value="Create Review" class="button" onclick="redirect('gateway.php');" />
											<p>You must be logged in to submit a review.</p>
										</div>
										
										<?php
									} else {
										if(isset($_REQUEST['reviews']) && ($_REQUEST['reviews'] == 'thanks')) {
											?>
											
											<div class="attention">
												<div class="attention-info attention-info-feedback">
													<span class="attention-info-title">Thank You For Your Review</span><br />
													Thank you for taking the time to review this product. Your review will become visible to other customers once approved.
												</div>
											</div>

											<?php
										} else {
											?>
										<div class="product-review-create" id="product-review-create" <?php echo (!$formReview->Valid) ? 'style="display: none;"' : ''; ?>>
<!--										<div class="product-review-create" id="product-review-create">
-->												<input type="button" name="create" value="Create Review" class="button" onclick="showReview();" />
												<p>Share you product experiences and thoughts with others.</p>
											</div>
											
											<?php /*?><div class="product-review-input" id="product-review-input" <?php echo ($formReview->Valid) ? 'style="display: none;"' : ''; ?>><?php */?>
											<div class="product-review-input" id="product-review-input">
												<?php
												if(!$formReview->Valid) {
													?>
							
													<div class="attention">
														<div class="attention-icon attention-icon-warning"></div>
														<div class="attention-info attention-info-warning">
															<span class="attention-info-title">Please Correct The Following</span><br />
															
															<ol>
															
																<?php
																for($i=0; $i<count($formReview->Errors); $i++) {
																	echo sprintf('<li>%s</li>', $formReview->Errors[$i]);
																}
																?>
																
															</ol>
														</div>
													</div>
													
													<?php
												}
												
												echo $formReview->Open();
												echo $formReview->GetHTML('confirm');
												echo $formReview->GetHTML('form');
												echo $formReview->GetHTML('tab');
												echo $formReview->GetHTML('pid');
												echo $formReview->GetHTML('cat');

												echo sprintf('<p>Please enter a title for your review <small>(50 chars. max)</small><br />%s</p>', $formReview->GetHTML('title'));
												echo sprintf('<p>Enter your review below<br />%s</p>', $formReview->GetHTML('review'));
												echo sprintf('<p>What would you rate this product?<br />%s</p>', $formReview->GetHTML('rating'));

												echo '<input name="submit" type="submit" value="Submit For Approval" class="button" />';

												echo $formReview->Close();
												?>
												
											</div>
										
											<?php
										}
									}
									
									if(!empty($product->Review)) {
										?>
									
										<table class="list">

											<?php
											foreach($product->Review as $review) {
												?>
												
												<tr>
													<td>
														<div class="product-stars">
															<?php
															$ratingStars = number_format($review['Rating'] * $GLOBALS['PRODUCT_REVIEW_RATINGS'], 1, '.', '');

															for($i=0; $i<$GLOBALS['PRODUCT_REVIEW_RATINGS']; $i++) {
																?>
																
																<div class="product-stars-item <?php echo (ceil($ratingStars) > $i) ? 'product-stars-item-solid' : ''; ?>"></div>
																
																<?php
															}
															?>
															
															<div class="product-stars-quote">&quot;<?php echo $review['Title']; ?>&quot;</div>
															<div class="clear"></div>
														</div>
														<div class="product-review-test">
															<p><span class="colour-black">By <?php echo !empty($review['Country_Name']) ? $review['Customer_Name'] : '<em>Anonymous</em>'; ?> (<?php echo !empty($review['Country_Name']) ? $review['Country_Name'] : '<em>Unknown</em>'; ?>), <?php echo date('j M Y', strtotime($review['Created_On'])); ?></span><br /><?php echo nl2br(stripslashes($review['Review'])); ?></p>
														</div>
													</td>
												</tr>
												
												<?php
											}
											?>

										</table>
										
										<?php
									}
									?>
							
								</div>
							</div>
							
							<div class="clear"></div>
						</div>
                          		<div id="menubodytitles" onclick="setContent('enquire');"><div id="menutitles"><a class="WhiteLnkSideMenu" title="Enquiry" href="#tab-content-item-enquire"><center>Enquiry</center></a></div></div>
				<div class="tab-content-item" id="tab-content-item-enquire" <?php echo ($tab == 'enquire') ? '' : 'style="display: none;"'; ?>>
<!--						<div class="tab-content-item" id="tab-content-item-enquire">
-->							<div class="tab-content-title">
								<a id="tab-enquire"></a>
								<h2>Product Enquiry</h2>
								enquire about <?php echo $product->Name; ?>
							</div>
							
							<?php
							if(isset($_REQUEST['enquire']) && ($_REQUEST['enquire'] == 'thanks')) {
								?>

								<div class="attention">
									<div class="attention-info attention-info-feedback">
										<span class="attention-info-title">Thank You For Your Enquiry</span><br />
										Your details have been sent to us and we will be in contact with you as soon as possible.
									</div>
								</div>
									
								<?php
							} else {
								if(!$formEnquiry->Valid) {
									?>
			
									<div class="attention">
										<div class="attention-icon attention-icon-warning"></div>
										<div class="attention-info attention-info-warning">
											<span class="attention-info-title">Please Correct The Following</span><br />
											
											<ol>
											
												<?php
												for($i=0; $i<count($formEnquiry->Errors); $i++) {
													echo sprintf('<li>%s</li>', $formEnquiry->Errors[$i]);
												}
												?>
												
											</ol>
										</div>
									</div>
									
									<?php
								}
							}
							?>
							
							<p>Please complete the fields below. Required fields are marked with an asterisk (*).</p>
							
							<?php												
							echo $formEnquiry->Open();
							echo $formEnquiry->GetHTML('confirm');
							echo $formEnquiry->GetHTML('form');
							echo $formEnquiry->GetHTML('tab');
							echo $formEnquiry->GetHTML('pid');
							echo $formEnquiry->GetHTML('cat');

							if(!$session->IsLoggedIn) {
								?>
								<table width="100%">
								<div class="form-block">
                                <tr><td>
									<div class="form-column">
										<p>Personal title <?php echo $formEnquiry->GetIcon('title'); ?><br /><?php echo $formEnquiry->GetHTML('title'); ?></p>
									</div></td></tr>
                                     <tr><td>
									<div class="form-column">
										<p>Business name <?php echo $formEnquiry->GetIcon('businessname'); ?><br /><?php echo $formEnquiry->GetHTML('businessname'); ?></p>
									</div></td></tr>
                                     <tr><td>
									<div class="form-column">
										<p>Form validation code <?php echo $formEnquiry->GetIcon('code'); ?><br /><?php echo $formEnquiry->GetHTML('code'); ?></p>
									</div></td></tr>
                                    <tr><td>
									<div class="form-column">
										<span class="captcha">
											<img src="securimage.php" alt="Click to change form validation image" onclick="this.src = 'securimage.php?sid=' + Math.random();" />
										</span>
										
										<object type="application/x-shockwave-flash" data="<?php echo rawurlencode('../ignition/packages/Securimage/securimage_play.swf?audio=/ignition/packages/Securimage/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000'); ?>" width="19" height="19">
											<param name="movie" value="<?php echo rawurlencode('../ignition/packages/Securimage/securimage_play.swf?audio=/ignition/packages/Securimage/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000'); ?>" />
										</object>

									</div>
                                    </td></tr>
									<div class="clear"></div>
								</div>
								
								<div class="form-block">
                                 <tr><td>
									<div class="form-column">
										<p>First name <?php echo $formEnquiry->GetIcon('firstname'); ?><br /><?php echo $formEnquiry->GetHTML('firstname'); ?></p>
									</div></td></tr>
                                     <tr><td>                                     
									<div class="form-column">
										<p>Last name <?php echo $formEnquiry->GetIcon('lastname'); ?><br /><?php echo $formEnquiry->GetHTML('lastname'); ?></p>
									</div></td></tr>
									<div class="clear"></div>
								</div>
								<div class="form-block">
                                 <tr><td>
									<div class="form-column">
										<p>E-mail address <?php echo $formEnquiry->GetIcon('email'); ?><br /><?php echo $formEnquiry->GetHTML('email'); ?></p>
									</div></td></tr>
                                     <tr><td>
									<div class="form-column">
										<p>Phone number <?php echo $formEnquiry->GetIcon('phone'); ?><br /><?php echo $formEnquiry->GetHTML('phone'); ?></p>
									</div></td></tr>
									<div class="clear"></div>
								</div>
								
								<?php
							}
							echo sprintf('<tr><td>');
							echo sprintf('<p>Enter your enquiry to us %s<br />%s</p>', $formEnquiry->GetIcon('message'), $formEnquiry->GetHTML('message'));
							echo sprintf('</td></tr>');
							echo sprintf('<tr><td>');
							echo '<input name="submit" type="submit" value="Submit Enquiry" class="button" />';
							echo sprintf('</td></tr>');
							echo $formEnquiry->Close();
							?>
							</table>
						</div>
						
						<?php
						if(strtolower($productType) == 'led') {
							if($session->IsLoggedIn) {
								if($hasCustomerBought) {
									?>
								
									<div class="tab-content-item" id="tab-content-item-examples" <?php echo ($tab == 'examples') ? '' : 'style="display: none;"'; ?>>
										<div class="tab-content-title">
											<a id="tab-examples"></a>
											<h2>Product Examples</h2>
											submit your product example for <?php echo $product->Name; ?>
										</div>
										
										<?php
										if(isset($_REQUEST['examples']) && ($_REQUEST['examples'] == 'thanks')) {
											?>

											<div class="attention">
												<div class="attention-info attention-info-feedback">
													<span class="attention-info-title">Thank You For Your Example</span><br />
													Your image has been sent to us and we will review and publish it as soon as possible.
												</div>
											</div>
												
											<?php
										} else {
											if(!$formExamples->Valid) {
												?>
						
												<div class="attention">
													<div class="attention-icon attention-icon-warning"></div>
													<div class="attention-info attention-info-warning">
														<span class="attention-info-title">Please Correct The Following</span><br />
														
														<ol>
														
															<?php
															for($i=0; $i<count($formExamples->Errors); $i++) {
																echo sprintf('<li>%s</li>', $formExamples->Errors[$i]);
															}
															?>
															
														</ol>
													</div>
												</div>
												
												<?php
											}
										}
										?>
										
										<p>Please complete the fields below. Required fields are marked with an asterisk (*).</p>
										
										<?php												
										echo $formExamples->Open();
										echo $formExamples->GetHTML('confirm');
										echo $formExamples->GetHTML('form');
										echo $formExamples->GetHTML('tab');
										echo $formExamples->GetHTML('pid');
										echo $formExamples->GetHTML('cat');

										echo sprintf('<p>Select your example image %s<br />%s</p>', $formExamples->GetIcon('image'), $formExamples->GetHTML('image'));
										echo '<input name="submit" type="submit" value="Submit Example" class="button" />';

										echo $formExamples->Close();
										?>
										
									</div>
									
									<?php
								}
							}
						}
						?>
						
					</div>
                    </div>
<?php /*?>					<?php include('../lib/templates/back_wspl.php'); ?>
					<?php include('../lib/templates/recent_wspl.php'); ?><?php */?>
</div>
</div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>