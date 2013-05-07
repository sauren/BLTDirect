<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLineDocument.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(3);

$customerId = (isset($_REQUEST['customerid'])) ? $_REQUEST['customerid'] : 0;
$enquiryId = (isset($_REQUEST['enquiryid'])) ? $_REQUEST['enquiryid'] : 0;

if($customerId > 0) {
	$customer = new Customer($customerId);
	$customer->Contact->Get();
} else {
	if(isset($_REQUEST['enquirylineid']) && ($_REQUEST['enquirylineid'] > 0)) {
		$enquiryLine = new EnquiryLine($_REQUEST['enquirylineid']);
		$enquiryId = $enquiryLine->Enquiry->ID;
	}

	if($enquiryId == 0) {
		redirect(sprintf("Location: enquiry_search.php"));
	}

	$enquiry = new Enquiry($enquiryId);
	$enquiry->Customer->Get();
	$enquiry->Customer->Contact->Get();
}

$sessionKey = sprintf('new-%s', $session->ID);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('customerid', 'Customer ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('enquiryid', 'Enquiry ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('enquirylineid', 'Enquiry ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('quickfind', 'Quickfind', 'text', '', 'numeric_unsigned', 1, 11);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$product = new Product();
		
		if($product->Get($form->GetValue('quickfind'))) {
			$product->GetDownloads();
			
			if($customerId > 0) {
				if(!isset($_SESSION['Enquiries'])) {
					$_SESSION['Enquiries'] = array();
				}

				if(!isset($_SESSION['Enquiries'][$sessionKey])) {
					$_SESSION['Enquiries'][$sessionKey] = array();
				}

				if(!isset($_SESSION['Enquiries'][$sessionKey]['Quotes'])) {
					$_SESSION['Enquiries'][$sessionKey]['Quotes'] = array();
				}

				for($i=0; $i<count($product->Download); $i++) {
					if(!empty($product->Download[$i]->file->FileName) && file_exists($GLOBALS['PRODUCT_DOWNLOAD_DIR_FS'].$product->Download[$i]->file->FileName)) {
						$destination = new IFile();
						$destination->OnConflict = "makeunique";
						$destination->Extensions = "";
						$destination->SetDirectory($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS']);
						$destination->FileName = $product->Download[$i]->file->FileName;
						$destination->SetName($destination->FileName);

						if($destination->Exists()) {
							$destination->CreateUniqueName($product->Download[$i]->file->FileName);
						}

						$product->Download[$i]->file->Copy($destination->Directory, $destination->FileName);

						$item = array();
						$item['FileName'] = $destination->FileName;
						$item['Public'] = 'Y';

						$_SESSION['Enquiries'][$sessionKey]['Documents'][$destination->FileName] = $item;
					}
				}
			} else {
				if($form->GetValue('enquirylineid') > 0) {
					for($i=0; $i<count($product->Download); $i++) {
						if(!empty($product->Download[$i]->file->FileName) && file_exists($GLOBALS['PRODUCT_DOWNLOAD_DIR_FS'].$product->Download[$i]->file->FileName)) {
							$lineDocument = new EnquiryLineDocument();
							$lineDocument->EnquiryLineID = $enquiryLine->ID;
							$lineDocument->IsPublic = 'Y';
				
							$destination = new IFile();
							$destination->OnConflict = "makeunique";
							$destination->Extensions = "";
							$destination->SetDirectory($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS']);
							$destination->FileName = $product->Download[$i]->file->FileName;
							$destination->SetName($destination->FileName);

							if($destination->Exists()) {
								$destination->CreateUniqueName($product->Download[$i]->file->FileName);
							}

							$product->Download[$i]->file->Copy($destination->Directory, $destination->FileName);

							$lineDocument->File->FileName = $destination->FileName;
							$lineDocument->Add();
						}
					}
					
				} else {
					if(!isset($_SESSION['Enquiries'])) {
						$_SESSION['Enquiries'] = array();
					}

					if(!isset($_SESSION['Enquiries'][$enquiry->ID])) {
						$_SESSION['Enquiries'][$enquiry->ID] = array();
					}

					if(!isset($_SESSION['Enquiries'][$enquiry->ID]['Quotes'])) {
						$_SESSION['Enquiries'][$enquiry->ID]['Quotes'] = array();
					}

					for($i=0; $i<count($product->Download); $i++) {
						if(!empty($product->Download[$i]->file->FileName) && file_exists($GLOBALS['PRODUCT_DOWNLOAD_DIR_FS'].$product->Download[$i]->file->FileName)) {
							$destination = new IFile();
							$destination->OnConflict = "makeunique";
							$destination->Extensions = "";
							$destination->SetDirectory($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS']);
							$destination->FileName = $product->Download[$i]->file->FileName;
							$destination->SetName($destination->FileName);

							if($destination->Exists()) {
								$destination->CreateUniqueName($product->Download[$i]->file->FileName);
							}

							$product->Download[$i]->file->Copy($destination->Directory, $destination->FileName);

							$item = array();
							$item['FileName'] = $destination->FileName;
							$item['Public'] = 'Y';

							$_SESSION['Enquiries'][$enquiry->ID]['Documents'][$destination->FileName] = $item;
						}
					}
				}
			}
		}
	}

	if($form->Valid) {
		if($customerId > 0) {
			redirect(sprintf("Location: enquiry_summary.php?customerid=%d", $customerId));
		} else {
			redirect(sprintf("Location: enquiry_details.php?enquiryid=%d", $enquiry->ID));
		}
	}
}

if($customerId > 0) {
	$page = new Page(sprintf('<a href="enquiry_summary.php?customerid=%d">Create New Enquiry</a> &gt; Attach Product Downloads to Enquiry', $customer->ID), 'Check the product downloads belonging to this customer which you want to attach to this enquiry.');
	$page->Display('header');
} else {
	$page = new Page(sprintf('<a href="enquiry_details.php?enquiryid=%d">Enquiry Details</a> &gt; Attach Product Downloads to Enquiry Ref: %s%s', $enquiry->ID, $enquiry->GetPrefix(), $enquiry->ID), 'Check the product downloads belonging to this customer which you want to attach to this enquiry.');
	$page->Display('header');
}

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('customerid');
echo $form->GetHTML('enquiryid');
echo $form->GetHTML('enquirylineid');

$window = new StandardWindow("Attach by product quickfind.");
$webForm = new StandardForm();

echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('quickfind'), $form->GetHTML('quickfind') . $form->GetIcon('quickfind'));
echo $webForm->AddRow('', '<input type="submit" name="attach" value="attach" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
	
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');