<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$enquiry = new Enquiry();

if(!$enquiry->Get($_REQUEST['enquiryid'])) {
	redirect(sprintf("Location: enquiry_search.php"));
}

$enquiry->Customer->Get();
$enquiry->Customer->Contact->Get();

$data = new DataQuery(sprintf("SELECT Enquiry_Closed_Type_ID FROM enquiry_closed_type WHERE Is_Default='Y'"));
$closeType = ($data->TotalRows > 0) ? mysql_real_escape_string($data->Row['Enquiry_Closed_Type_ID']) : 0;
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('enquiryid', 'Enquiry ID', 'hidden', $enquiry->ID, 'numeric_unsigned', 1, 11);
$form->AddField('closetype', 'Close Type', 'select', $closeType, 'numeric_unsigned', 1, 11, false);
$form->AddField('email', 'Send Email To Customer?', 'checkbox', 'T', 'boolean', 1, 1, false);

$form->AddOption('closetype', '0', '');

$data = new DataQuery(sprintf("SELECT * FROM enquiry_closed_type ORDER BY Name"));
while($data->Row) {
	$form->AddOption('closetype', $data->Row['Enquiry_Closed_Type_ID'], $data->Row['Name']);

	$data->Next();
}
$data->Disconnect();

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()){		
		$enquiry->Close($form->GetValue('closetype'));
		if($_REQUEST['email'])
		{
			$enquiry->SendClosed();
		}
		
		redirect(sprintf("Location: enquiry_details.php?enquiryid=%d", $enquiry->ID));
	}
}

$page = new Page(sprintf('<a href="enquiry_details.php?enquiryid=%d">%s%d Enquiry Details for %s</a> &gt; Close Enquiry', $enquiry->ID, $enquiry->GetPrefix(), $enquiry->ID, trim(sprintf('%s %s %s', $enquiry->Customer->Contact->Person->Title, $enquiry->Customer->Contact->Person->Name, $enquiry->Customer->Contact->Person->LastName))), 'Close this enquiry here.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo "<br>";
}

$window = new StandardWindow('Close Enquiry');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('enquiryid');
echo $window->Open();
echo $window->AddHeader('Select an enquiry close type.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('closetype'), $form->GetHTML('closetype') . $form->GetIcon('closetype'));
echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email') . $form->GetIcon('email'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'enquiry_details.php?enquiryid=%d\';"> <input type="submit" name="close enquiry" value="close enquiry" class="btn" tabindex="%s">', $enquiry->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');