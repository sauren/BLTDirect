<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLineDocument.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LibraryFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');

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
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('customerid', 'Customer ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('enquiryid', 'Enquiry ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('enquirylineid', 'Enquiry ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('document', 'Upload Document', 'file', '', 'file', NULL, NULL, false);
$form->AddField('library', '', 'hidden', 0, 'numeric_unsigned', 1, 11);
$form->AddField('copy', 'Add From Library', 'text', '', 'paragraph', 1, 255, false, 'onfocus="this.blur();"');
$form->AddField('public', 'Public', 'checkbox', 'Y', 'boolean', 1, 1, false);

if($action == 'add' && isset($_REQUEST['confirm'])){
	if(($form->GetValue('library') == 0) && empty($_FILES['document']['name'])) {
		$form->AddError('Please select a document to add or upload.');
	}

	if($form->Validate()){
		if($customerId > 0) {
			if(!isset($_SESSION['Enquiries'])) {
				$_SESSION['Enquiries'] = array();
			}

			if(!isset($_SESSION['Enquiries'][$sessionKey])) {
				$_SESSION['Enquiries'][$sessionKey] = array();
			}

			if(!isset($_SESSION['Enquiries'][$sessionKey]['Documents'])) {
				$_SESSION['Enquiries'][$sessionKey]['Documents'] = array();
			}

			if($form->GetValue('library') > 0) {
				$file = new LibraryFile($form->GetValue('library'));

				if(!empty($file->Src->FileName) && file_exists($GLOBALS['FILE_DIR_FS'].$file->Src->FileName)) {
					$destination = new IFile();
					$destination->OnConflict = "makeunique";
					$destination->Extensions = "";
					$destination->SetDirectory($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS']);
					$destination->FileName = $file->Src->FileName;
					$destination->SetName($destination->FileName);

					if($destination->Exists()) {
						$destination->CreateUniqueName($file->Src->FileName);
					}

					$file->Src->Copy($destination->Directory, $destination->FileName);

					$item = array();
					$item['FileName'] = $destination->FileName;
					$item['Public'] = $form->GetValue('public');

					$_SESSION['Enquiries'][$sessionKey]['Documents'][$destination->FileName] = $item;
				}
			} else {
				$destination = new IFile();
				$destination->OnConflict = "makeunique";
				$destination->Extensions = "";
				$destination->SetDirectory($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS']);

				if(!$destination->Upload('document')){
					for($i=0; $i < count($destination->Errors); $i++){
						$form->AddError($destination->Errors[$i]);
					}
				} else {
					$item = array();
					$item['FileName'] = $destination->FileName;
					$item['Public'] = $form->GetValue('public');

					$_SESSION['Enquiries'][$sessionKey]['Documents'][$destination->FileName] = $item;
				}
			}
		} else {
			if($form->GetValue('enquirylineid') > 0) {
				$lineDocument = new EnquiryLineDocument();
				$lineDocument->EnquiryLineID = $enquiryLine->ID;
				$lineDocument->IsPublic = (isset($_REQUEST['public']) && ($_REQUEST['public'] == 'Y')) ? $_REQUEST['public'] : 'N';

				if($form->GetValue('library') > 0) {
					$file = new LibraryFile($form->GetValue('library'));

					if(!empty($file->Src->FileName) && file_exists($GLOBALS['FILE_DIR_FS'].$file->Src->FileName)) {
						$destination = new IFile();
						$destination->OnConflict = "makeunique";
						$destination->Extensions = "";
						$destination->SetDirectory($GLOBALS['ENQUIRY_DOCUMENT_DIR_FS']);
						$destination->FileName = $file->Src->FileName;
						$destination->SetName($destination->FileName);

						if($destination->Exists()) {
							$destination->CreateUniqueName($file->Src->FileName);
						}

						$file->Src->Copy($destination->Directory, $destination->FileName);

						$lineDocument->File->FileName = $destination->FileName;
						$lineDocument->Add();
					}
				} else {
					if(!$lineDocument->Add('document')) {
						for($i=0; $i < count($lineDocument->Errors); $i++){
							$form->AddError($lineDocument->Errors[$i]);
						}
					}
				}
			} else {
				if(!isset($_SESSION['Enquiries'])) {
					$_SESSION['Enquiries'] = array();
				}

				if(!isset($_SESSION['Enquiries'][$enquiry->ID])) {
					$_SESSION['Enquiries'][$enquiry->ID] = array();
				}

				if(!isset($_SESSION['Enquiries'][$enquiry->ID]['Documents'])) {
					$_SESSION['Enquiries'][$enquiry->ID]['Documents'] = array();
				}

				if($form->GetValue('library') > 0) {
					$file = new LibraryFile($form->GetValue('library'));

					if(!empty($file->Src->FileName) && file_exists($GLOBALS['FILE_DIR_FS'].$file->Src->FileName)) {
						$destination = new IFile();
						$destination->OnConflict = "makeunique";
						$destination->Extensions = "";
						$destination->SetDirectory($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS']);
						$destination->FileName = $file->Src->FileName;
						$destination->SetName($destination->FileName);

						if($destination->Exists()) {
							$destination->CreateUniqueName($file->Src->FileName);
						}

						$file->Src->Copy($destination->Directory, $destination->FileName);

						$item = array();
						$item['FileName'] = $destination->FileName;
						$item['Public'] = $form->GetValue('public');

						$_SESSION['Enquiries'][$enquiry->ID]['Documents'][$destination->FileName] = $item;
					}
				} else {
					$destination = new IFile();
					$destination->OnConflict = "makeunique";
					$destination->Extensions = "";
					$destination->SetDirectory($GLOBALS['TEMP_ENQUIRY_DOCUMENT_DIR_FS']);

					if(!$destination->Upload('document')){
						for($i=0; $i < count($destination->Errors); $i++){
							$form->AddError($destination->Errors[$i]);
						}
					} else {
						$item = array();
						$item['FileName'] = $destination->FileName;
						$item['Public'] = $form->GetValue('public');

						$_SESSION['Enquiries'][$enquiry->ID]['Documents'][$destination->FileName] = $item;
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

$script = sprintf('<script language="javascript" type="text/javascript">
	var foundFile = function(id, title) {
		var e = document.getElementById(\'library\');
		if(e) {
			e.value = id;
		}

		e = document.getElementById(\'copy\');
		if(e) {
			e.value = title;
		}
	}
	</script>');

if($customerId > 0) {
	$page = new Page(sprintf('<a href="enquiry_summary.php?customerid=%d">Create New Enquiry</a> &gt; Attach Document to Enquiry', $customer->ID), 'Use the browse button to find a document to attach to this enquiry.');
} else {
	$page = new Page(sprintf('<a href="enquiry_details.php?enquiryid=%d">Enquiry Details</a> &gt; Attach Document to Enquiry Ref: %s%s', $enquiry->ID, $enquiry->GetPrefix(), $enquiry->ID), 'Use the browse button to find a document to attach to this enquiry.');
}

$page->AddToHead($script);
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

$window = new StandardWindow("Add a Document.");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('customerid');
echo $form->GetHTML('enquiryid');
echo $form->GetHTML('enquirylineid');
echo $form->GetHTML('library');
echo $window->Open();

echo $window->AddHeader('Browse for a document or add from the file library. Please note library files will take preference over selected local files');
echo $window->OpenContent();
echo $webForm->Open();
$temp_1 = '<a href="javascript:popUrl(\'popFindFile.php?callback=foundFile\', 650, 500);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
echo $webForm->AddRow($form->GetLabel('copy') . $temp_1, $form->GetHTML('copy'));
echo $webForm->AddRow($form->GetLabel('document'), $form->GetHTML('document'));
echo $webForm->Close();
echo $window->CloseContent();

echo $window->AddHeader('Configure enquiry document settings.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('public'), $form->GetHTML('public'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href=\'enquiry_details.php?enquiryid=%d\'" /> <input type="submit" name="add" value="add" class="btn" />', $enquiry->ID));
echo $webForm->Close();
echo $window->CloseContent();

echo $window->Close();
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');
?>