<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(3);

$priceEnquiry = new PriceEnquiry($_REQUEST['id']);

if(strtolower($priceEnquiry->Status) == 'complete') {
	redirect('Location: price_enquiries_pending.php');
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Price Enquiry ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('products', 'Products', 'text', '10', 'numeric_unsigned', 1, 11);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$split = $form->GetValue('products');
		
		if($split <= 0) {
			$form->AddError('The split amount must be greater than zero.', 'products');
		}
		
		if($form->Valid) {
			$priceEnquiry->GetLines();
			$priceEnquiry->GetQuantities();
			$priceEnquiry->GetSuppliers();

			for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
				$priceEnquiry->Supplier[$j]->GetLines();
			}

			$amount = ceil(count($priceEnquiry->Line) / $split);

			if($amount > 1) {
				$products = array();
				$index = 0;
				$count = 0;
				
				foreach($priceEnquiry->Line as $line) {
					if(!isset($products[$index])) {
						$products[$index] = array();
					}
					
					$products[$index][] = array('OldLine' => $line, 'NewLine' => null);
					
					$count++;
					
					if($count >= $split) {
						$count = 0;
						$index++;
					}
				}
				
				for($i=0; $i<$amount; $i++) {
					$splitPriceEnquiry = new PriceEnquiry($priceEnquiry->ID);
					$splitPriceEnquiry->Add();
					
					for($j=0; $j<count($products[$i]); $j++) {
						$line = new PriceEnquiryLine($products[$i][$j]['OldLine']->ID);
						$line->PriceEnquiryID = $splitPriceEnquiry->ID;
						$line->Add();
						
						$products[$i][$j]['NewLine'] = $line;
					}
					
					for($j=0; $j<count($priceEnquiry->Quantity); $j++) {
						$priceEnquiry->Quantity[$j]->PriceEnquiryID = $splitPriceEnquiry->ID;
						$priceEnquiry->Quantity[$j]->Add();
					}
					
					for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
						$priceEnquiry->Supplier[$j]->PriceEnquiryID = $splitPriceEnquiry->ID;
						$priceEnquiry->Supplier[$j]->Add();
						
						for($k=0; $k<count($priceEnquiry->Supplier[$j]->Line); $k++) {
							for($h=0; $h<count($products[$i]); $h++) {
								if($products[$i][$h]['OldLine']->ID == $priceEnquiry->Supplier[$j]->Line[$k]->PriceEnquiryLineID) {
									$priceEnquiry->Supplier[$j]->Line[$k]->PriceEnquirySupplierID = $priceEnquiry->Supplier[$j]->ID;
									$priceEnquiry->Supplier[$j]->Line[$k]->PriceEnquiryLineID = $products[$i][$h]['NewLine']->ID;
									$priceEnquiry->Supplier[$j]->Line[$k]->Add();
								}
							}
						}
					}
					
					$splitPriceEnquiry->Recalculate();
				}
			}
			
			redirect(sprintf("Location: ?id=%d", $form->GetValue('id')));
		}
	}
}

$page = new Page(sprintf('[#%d] Price Enquiry Split', $priceEnquiry->ID), 'Split this price enquiry into mutliples with the specified number of products in each.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Split products');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');

echo $window->Open();
echo $window->AddHeader('Enter the new split amount');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('products'), $form->GetHTML('products'), $form->GetHTML('products'));
echo $webForm->AddRow('', sprintf('<input type="submit" name="split" value="split" class="btn" tabindex="%s" />', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');