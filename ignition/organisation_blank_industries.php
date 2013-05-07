<?php
	require_once('lib/common/app_header.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	ini_set('max_execution_time', 300);

	$session->Secure(2);

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('search', 'Search Organisation', 'text', '', 'paragraph', 1, 255, false);

	$orgForm = new Form($_SERVER['PHP_SELF']);
	$orgForm->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$orgForm->AddField('search', 'Search Organisation', 'hidden', '', 'paragraph', 1, 255, false);
	$orgForm->AddField('industry', 'Industry', 'select', '', 'anything', 1, 3, false, "onchange='changeTypes(this)'");
	$orgForm->AddOption('industry', 0, '');

	$industryData = new DataQuery("select Industry_ID, Industry_Name from organisation_industry");
	while($industryData->Row){
		$orgForm->AddOption('industry', $industryData->Row['Industry_ID'], $industryData->Row['Industry_Name']);
		$industryData->Next();
	}
	$industryData->Disconnect();

	$sql = "SELECT c.Contact_ID, o.Org_Name, o.Org_ID
		FROM contact AS c
		INNER JOIN organisation AS o ON c.Org_ID = o.Org_ID
		WHERE c.Contact_Type='O' AND o.Industry_ID = 0";

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

					if($value <= 0){
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

	$page = new Page('Organisations without Industries', '');
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
	echo $orgForm->GetHTML('search');

	$table = new DataTable('organisations');
	$table->ExtractVars = '';
	$table->SetSQL($sql);
	$table->AddField('ID', 'Org_ID', 'center');
	$table->AddField('Organisation', 'Org_Name', 'left');
	$table->AddInput('Select / Deselect All <input id="selectAllCheckBox" style="margin:0px; margin-left:5px;" type="checkbox" value="" name="is" onchange="changeChecks(this)" />', 'N', 'Y', 'is', 'Org_ID', 'checkbox');
	$table->AddInput('Industry Type', 'Y', '', 'industry', 'Org_ID', 'select');
	$table->AddInputOption('Industry Type', '0', '');
	$data = new DataQuery("select Industry_ID, Industry_Name from organisation_industry");
	while($data->Row) {
		$table->AddInputOption('Industry Type', $data->Row['Industry_ID'], $data->Row['Industry_Name']);
		$data->Next();
	}
	$data->Disconnect();

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
?>