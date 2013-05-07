<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Branch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
$session->Secure(2);

$branch = new Branch($_REQUEST['bid']);
$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('bid','bid','hidden',$_REQUEST['bid'],'numeric_unsigned',0,11);
$branch = new Branch($form->GetValue('bid'));
$form->AddField('name','Branch Name','text',$branch->Name,'anything',1,60);
$form->AddField('hq','Is this branch your Head Quarters?','select',$branch->HQ,'alpha',0,4);
$form->AddOption('hq','N','No');
$form->AddOption('hq','Y','Yes');
//Address information
$form->AddField('address1', 'Property Name/Number', 'text', $branch->Address->Line1, 'anything', 1, 150,false);
$form->AddField('address2', 'Street', 'text', $branch->Address->Line2, 'anything', 1, 150,false);
$form->AddField('address3', 'Area', 'text', $branch->Address->Line3, 'anything', 1, 150, false);
$form->AddField('city', 'City', 'text', $branch->Address->City, 'anything', 1, 150,false);
// Country
$form->AddField('country', 'Country', 'select', $branch->Address->Country->ID, 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
$form->AddOption('country', '0', '');
$form->AddOption('country', '222', 'United Kingdom');

$data = new DataQuery("select * from countries order by Country asc");
while($data->Row){
	$form->AddOption('country', $data->Row['Country_ID'], $data->Row['Country']);
	$data->Next();
}
$data->Disconnect();
unset($data);
// Region
$regionCount = 0;
$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($form->GetValue('country'))));
$regionCount = $region->TotalRows;
if($regionCount > 0){
	$form->AddField('region', 'Region', 'select', $branch->Address->Region->ID, 'numeric_unsigned', 1, 11, false);
	$form->AddOption('region', '0', '');
	while($region->Row){
		$form->AddOption('region', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
} else {
	$form->AddField('region', 'Region', 'select', '0', 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
	$form->AddOption('region', '0', '');
}
$region->Disconnect();
unset($region);
$form->AddField('postcode', 'Postcode', 'text', $branch->Address->Zip, 'anything', 1, 10);

$form->AddField('phone1','Phone 1','text',$branch->Phone1,'anything',1,15,false);
$form->AddField('phone1ext','Phone 1 Ext','text',$branch->Phone1Ext,'anything',1,5,false);
$form->AddField('phone2','Phone 2','text',$branch->Phone2,'anything',1,15,false);
$form->AddField('phone2ext','Phone 2 Ext','text',$branch->Phone2Ext,'anything',1,5,false);
$form->AddField('fax','fax','text',$branch->Fax,'anything',1,15,false);
$form->AddField('email','Email','text',$branch->Email,'email',NULL,NULL);
$form->AddField('company','Company Number','text',$branch->Company,'anything',1,100,false);
$form->AddField('tax','Tax Number','text',$branch->Tax,'anything',1,100,false);

$form->AddField('orgType', 'Organisation Type', 'select', $branch->Org->ID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('orgType', '0', '');
$orgTypes = new DataQuery('select * from organisation_type order by Org_Type asc');
while($orgTypes->Row){
	$form->AddOption('orgType', $orgTypes->Row['Org_Type_ID'], $orgTypes->Row['Org_Type']);
	$orgTypes->Next();
}
$orgTypes->Disconnect();

$form->AddField('public','Can Public Contact','select',$branch->PublicContact,'alpha',0,4);
$form->AddOption('public','N','No');
$form->AddOption('public','Y','Yes');

$form->AddField('support','Is this branch a customer service and support branch?','select',$branch->Support,'alpha',0,4);
$form->AddOption('support','N','No');
$form->AddOption('support','Y','Yes');

$form->AddField('sales','Is this branch a sales branch?','select',$branch->Sales,'alpha',0,4);
$form->AddOption('sales','N','No');
$form->AddOption('sales','Y','Yes');

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	$form->Validate();
	$branch->Name = $form->GetValue('name');
	$branch->HQ = $form->GetValue('hq');
	$branch->Address->Line1 = $form->GetValue('address1');
	$branch->Address->Line2 = $form->GetValue('address2');
	$branch->Address->Line3 = $form->GetValue('address3');
	$branch->Address->City = $form->GetValue('city');
	$branch->Address->Country->ID = $form->GetValue('country');
	$branch->Address->Region->ID = $form->GetValue('region');
	$branch->Address->Zip = $form->GetValue('postcode');
	$branch->Phone1 = $form->GetValue('phone1');
	$branch->Phone1Ext = $form->GetValue('phone1ext');
	$branch->Phone2 = $form->GetValue('phone2');
	$branch->Phone2Ext = $form->GetValue('phone2ext');
	$branch->Fax = $form->GetValue('fax');
	$branch->Email = $form->GetValue('email');
	$branch->Tax = $form->GetValue('tax');
	$branch->Company = $form->GetValue('company');
	$branch->Org->ID = $form->GetValue('orgType');
	$branch->PublicContact = $form->GetValue('public');
	$branch->Support = $form->GetValue('support');
	$branch->Sales = $form->GetValue('sales');
	if($form->Valid){
		$branch->Update();
		redirect('Location: branch_view.php');
	}
}

$page = new Page('New branch','Here you can add the details of a branch of the company');
$page->LinkScript('js/regions.php');
$page->Display('header');
//show errors if validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
$window = new StandardWindow('Please change your details');
$webForm = new StandardForm();
echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('action');
echo $form->GetHTML('bid');
echo $window->Open();
echo $window->AddHeader('Branch details');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('name'),$form->GetHTML('name').$form->GetIcon('name'));
echo $webForm->AddRow($form->GetLabel('hq'),$form->GetHTML('hq').$form->GetIcon('hq'));
echo $webForm->AddRow($form->GetLabel('tax'),$form->GetHTML('tax').$form->GetIcon('tax'));
echo $webForm->AddRow($form->GetLabel('company'),$form->GetHTML('company').$form->GetIcon('company'));
echo $webForm->AddRow($form->GetLabel('orgType'),$form->GetHTML('orgType').$form->GetIcon('orgType'));
echo $webForm->AddRow($form->GetLabel('public'),$form->GetHTML('public').$form->GetIcon('public'));
echo $webForm->AddRow($form->GetLabel('support'),$form->GetHTML('support').$form->GetIcon('support'));
echo $webForm->AddRow($form->GetLabel('sales'),$form->GetHTML('sales').$form->GetIcon('sales'));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->AddHeader('Contact Details');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('fax'),$form->GetHTML('fax').$form->GetIcon('fax'));
echo $webForm->AddRow($form->GetLabel('phone1'),$form->GetHTML('phone1').$form->GetIcon('phone1'));
echo $webForm->AddRow($form->GetLabel('phone1ext'),$form->GetHTML('phone1ext').$form->GetIcon('phone1ext'));
echo $webForm->AddRow($form->GetLabel('phone2'),$form->GetHTML('phone2').$form->GetIcon('phone2'));
echo $webForm->AddRow($form->GetLabel('phone2ext'),$form->GetHTML('phone2ext').$form->GetIcon('phone2ext'));
echo $webForm->AddRow($form->GetLabel('email'),$form->GetHTML('email').$form->GetIcon('email'));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->AddHeader('Address Details');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('address1'),$form->GetHTML('address1').$form->GetIcon('address1'));
echo $webForm->AddRow($form->GetLabel('address2'),$form->GetHTML('address2').$form->GetIcon('address2'));
echo $webForm->AddRow($form->GetLabel('address3'),$form->GetHTML('address3').$form->GetIcon('address3'));
echo $webForm->AddRow($form->GetLabel('city'),$form->GetHTML('city').$form->GetIcon('city'));
echo $webForm->AddRow($form->GetLabel('country'),$form->GetHTML('country').$form->GetIcon('country'));
echo $webForm->AddRow($form->GetLabel('region'),$form->GetHTML('region').$form->GetIcon('region'));
echo $webForm->AddRow($form->GetLabel('postcode'),$form->GetHTML('postcode').$form->GetIcon('postcode'));
echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'branch_view.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();
echo "<br>";
$page->Display('footer');
?>