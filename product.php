<?php
require_once('lib/common/appHeader.php');

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
require_once($GLOBALS['DIR_WS_ROOT'] . 'lib/common/yt_video_feed.php');
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

require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');