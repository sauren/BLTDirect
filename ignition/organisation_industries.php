<?php
require_once ('lib/common/app_header.php');

if($action == 'add') {
	$session->Secure(3);
	add();
	exit();
} elseif($action == 'remove') {
	$session->Secure(3);
	remove();
	exit();
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit();
} elseif($action == 'vieworganisations') {
	$session->Secure(2);
	viewOrganisations();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function remove() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrganisationIndustry.php');
	
	if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$industry = new OrganisationIndustry();
		$industry->Delete($_REQUEST['id']);
		
		$data = new DataQuery(sprintf("UPDATE organisation SET Industry_ID = 0 WHERE Industry_ID = %d", mysql_real_escape_string($_REQUEST['id'])));
		$data->Disconnect();
	}
	
	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrganisationIndustry.php');
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Industry Name', 'text', '', 'anything', 1, 128, true);
	
	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			$industry = new OrganisationIndustry();
			$industry->Name = $form->GetValue('name');
			$industry->Add();
			
			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}
	
	$page = new Page(sprintf('<a href="%s">Organisation Industries</a> &gt; Add Type', $_SERVER['PHP_SELF']), 'Add a new organisation industry here.');
	$page->AddOnLoad("document.getElementById('name').focus();");
	$page->Display('header');
	
	if (!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow('Adding a Organisation Industry');
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Enter a organisation industry.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'organisation_industries.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function update() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrganisationIndustry.php');
	
	$industry = new OrganisationIndustry($_REQUEST['id']);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', $industry->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Industry Name', 'text', $industry->Name, 'anything', 1, 128, true);
	
	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			$industry->Name = $form->GetValue('name');
			$industry->Update();
			
			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}
	
	$page = new Page(sprintf('<a href="%s">Organisation Industries</a> &gt; Edit Industry', $_SERVER['PHP_SELF']), 'Edit this organisation industry here.');
	$page->AddOnLoad("document.getElementById('name').focus();");
	$page->Display('header');
	
	if (!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow('Editing a Organisation Industry');
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Enter a organisation industry.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'organisation_industries.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function view() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	
	$page = new Page('Organisation Industries', 'Listing all available organisation industries.');
	$page->Display('header');

	$totalUnknown = 0;
	$data = new DataQuery("SELECT count(o.Org_ID) as Total_Unknown
FROM contact AS c
INNER JOIN organisation AS o ON c.Org_ID = o.Org_ID
WHERE c.Contact_Type='O' AND o.Industry_ID = 0");
	if($data->TotalRows > 0){
		$totalUnknown = $data->Row['Total_Unknown'];
	}
	$data->Disconnect();

	echo 'Total Organisations with an Unknown Industry : ' . $totalUnknown;
	echo '<br /><br />';
	
	$table = new DataTable('types');
	$table->SetSQL("SELECT oi.Industry_ID, oi.Industry_Name, count(o.Org_ID) as Count
FROM organisation_industry oi
LEFT JOIN organisation o ON oi.Industry_ID = o.Industry_ID
GROUP BY oi.Industry_ID");
	$table->AddField("ID#", "Industry_ID");
	$table->AddField("Industry Type", "Industry_Name", "left");
	$table->AddField("Organisations", "Count", "left");
	$table->SetMaxRows(100);
	$table->SetOrderBy("Industry_Name");
	$table->AddLink("organisation_industries.php?action=viewOrganisations&id=%s", "<img src=\"./images/folderopen.gif\" alt=\"View\" border=\"0\">", "Industry_ID");
	$table->AddLink("organisation_industries.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Industry_ID");
	$table->AddLink("javascript:confirmRequest('organisation_industries.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Industry_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add new type" class="btn" onclick="window.location.href=\'organisation_industries.php?action=add\'">';
	
	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function viewOrganisations() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	ini_set('max_execution_time', 300);

	$industries = array();
	$industryData = new DataQuery("select Industry_ID, Industry_Name from organisation_industry");
	while($industryData->Row){
		$industries[$industryData->Row['Industry_ID']] = $industryData->Row['Industry_Name'];
		$industryData->Next();
	}
	$industryData->Disconnect();

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('action', '', 'hidden', 'vieworganisations', 'alpha', 17, 17);
	$form->AddField('id', '', 'hidden', $_REQUEST['id'], 'anything', 1, 3);
	$form->AddField('search', 'Search Organisation', 'text', '', 'paragraph', 1, 255, false);

	$orgForm = new Form($_SERVER['PHP_SELF']);
	$orgForm->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$orgForm->AddField('action', '', 'hidden', 'vieworganisations', 'alpha', 17, 17);
	$orgForm->AddField('id', '', 'hidden', $_REQUEST['id'], 'anything', 1, 3);
	$orgForm->AddField('industry', 'Industry', 'select', '', 'anything', 1, 3, false, "onchange='changeTypes(this)'");
	$orgForm->AddOption('industry', 0, '');		
	foreach($industries as $id => $name){
		$orgForm->AddOption('industry', $id, $name);
	}

	$sql = sprintf("SELECT c.Contact_ID, o.Org_Name, o.Org_ID
		FROM contact AS c
		INNER JOIN organisation AS o ON c.Org_ID = o.Org_ID
		WHERE c.Contact_Type='O' AND o.Industry_ID = %d", $_REQUEST['id']);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			if(strlen($form->GetValue('search')) > 0) {
				$sql .= sprintf(" AND o.Org_Name_Search LIKE '%%%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $form->GetValue('search'))));
			}
		}

		if($orgForm->Validate()){
			if(isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'update'){
				$keyPrefixLength = strlen('industry_');
				foreach($_REQUEST as $key => $value){
					if(stristr($key, 'industry_') === false){
						continue;
					}

					if($value <= 0 || $value == $_REQUEST['id']){
						continue;
					}

					$organisation = trim(substr($key, $keyPrefixLength));
					new DataQuery(sprintf("update organisation
						set Industry_ID = %d
						where Org_ID = %d", mysql_real_escape_string($value), mysql_real_escape_string($organisation)));
				}
			}
		}
	}

	$page = new Page(sprintf('<a href="%s">Organisation Industries</a> &gt; View Industry', $_SERVER['PHP_SELF']), '');
	$page->LinkScript('./js/jquery-1.8.0.min.js');
	$page->AddToHead('<script>
		function changeChecks(obj){
			var inputs = $("input:checkbox").not("#selectAllCheckBox");
			inputs.each(function(){
				if(obj.checked){
					$(this).attr("checked", true);
				} else {
					$(this).attr("checked", false);					
				}
			});
		}

		function changeTypes(obj){
			var inputs = $("input:checkbox").not("#selectAllCheckBox");
			inputs.each(function(){
				if($(this).attr("checked")){
					var name = $(this).attr("name").replace("is_", "industry_");
					$(\'select[name="\' + name + \'"]\').val($(obj).val());
				}
			});
		}
	</script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}


	$window = new StandardWindow("Search for an Organisation.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Search for an Organisation using the fields below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('search'), $form->GetHTML('search'));
	echo $webForm->AddRow('', '<input type="submit" name="searchButton" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$window = new StandardWindow("Change an Organisations Industry.");
	$webForm = new StandardForm;

	echo $orgForm->Open();
	echo $orgForm->GetHTML('confirm');
	echo $orgForm->GetHTML('action');
	echo $orgForm->GetHTML('id');

	$table = new DataTable('organisations');
	$table->ExtractVars = '';
	$table->SetSQL($sql);
	$table->AddField('ID', 'Org_ID', 'center');
	$table->AddField('Organisation', 'Org_Name', 'left');
	$table->AddInput('Select / Deselect All <input id="selectAllCheckBox" style="margin:0px; margin-left:5px;" type="checkbox" value="" name="is" onchange="changeChecks(this)" />', 'N', 'Y', 'is', 'Org_ID', 'checkbox');
	$table->AddInput('Industry Type', 'Y', '', 'industry', 'Org_ID', 'select');
	if(isset($_REQUEST['id']) && $_REQUEST['id'] > 0){
		$table->AddInputOption('Industry Type', $_REQUEST['id'], $industries[$_REQUEST['id']]);
	} else {
		$table->AddInputOption('Industry Type', 0, '');
	}
	foreach($industries as $id => $name){
		if(isset($_REQUEST['id']) && $_REQUEST['id'] == $id){
			continue;
		}

		$table->AddInputOption('Industry Type', $id, $name);
	}

	$table->SetMaxRows(100);
	$table->SetOrderBy("Org_ID");
	$table->SetOrder("ASC");
	$table->Finalise();

	echo '<br />';
	$table->DisplayTable();
	$table->DisplayNavigation();
	echo '<br />';

	echo $window->Open();
	echo $window->AddHeader('Change an Organisations Industry.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($orgForm->GetLabel('industry'), $orgForm->GetHTML('industry'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="update" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $orgForm->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>