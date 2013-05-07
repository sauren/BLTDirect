<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');

$session->Secure(3);

$priceEnquiry = new PriceEnquiry($_REQUEST['id']);

if(strtolower($priceEnquiry->Status) == 'complete') {
	redirect(sprintf("Location: price_enquiry_details.php?id=%d", $priceEnquiry->ID));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'addfly', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Price Enquiry ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('quantity', 'Quantity', 'text', '', 'numeric_unsigned', 1, 11);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		if($form->GetValue('quantity') <= 1) {
			$form->AddError('Quantity must be greater than \'1\'.', 'quantity');
		}

		if($form->Valid) {
			$priceEnquiry->GetQuantities();

			for($i=0; $i<count($priceEnquiry->Quantity); $i++) {
				if($form->GetValue('quantity') == $priceEnquiry->Quantity[$i]->Quantity) {
					$form->AddError(sprintf('The quantity \'%s\' already exists for this price enquiry.', $form->GetValue('quantity')), 'quantity');
					break;
				}
			}

			if($form->Valid) {
				$priceEnquiry->AddQuantity($form->GetValue('quantity'));

				redirect(sprintf("Location: price_enquiry_details.php?id=%d", $priceEnquiry->ID));
			}
		}
	}
}

$page = new Page(sprintf('<a href="price_enquiry_details.php?id=%d">[#%d] Price Enquiry Details</a> &gt; Add Quantity', $priceEnquiry->ID, $priceEnquiry->ID), 'Add quantities for this price enquiry.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('New quantity');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');

echo $window->Open();
echo $window->AddHeader('Enter the new quantity');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity'), $form->GetHTML('quantity'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href=\'price_enquiry_details.php?id=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $priceEnquiry->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');