<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CsvImport.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

$session->Secure(2);

if($action == 'step2') {
	step2();
	exit;
} else {
	step1();
	exit;
}

function checkField($field, $fields) {
	foreach($fields as $key=>$value) {
		if($fields[$key] == $field) {
			return $key;
		}
	}

	return 0;
}

function step2() {
	$fields = array('' => '', 'quickfind' => 'Quickfind', 'sku' =>  'SKU', 'cost' => 'Supplier Cost');

    $form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'step2', 'alpha_numeric', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('supplier', 'Supplier', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('file', 'CSV File', 'hidden', '', 'link_relative', 3, 255);
	$form->AddField('delimit', 'Values Separated by', 'hidden', ',', 'paragraph', 1, 1);
	$form->AddField('encl', 'Values Enclosed in', 'hidden', '"', 'paragraph', 1, 7);

	$file = new IFile($form->GetValue('file'), "./temp");

	$csv = new CsvImport($file->Directory . "/" . $file->FileName, stripslashes($form->GetValue('delimit')), stripslashes($form->GetValue('encl')));
	$csv->HasFieldNames = true;

	if(!$csv->Open()) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	} else {
		for($i=0; $i < count($csv->FieldNames); $i++){
			$form->AddField('field'.$i, $csv->FieldNames[$i], 'select', checkField($csv->FieldNames[$i], $fields), 'alpha_numeric', 0, 255, false);

			foreach($fields as $key=>$value) {
				$form->AddOption('field'.$i, $key, $value);
			}
		}

		if(isset($_REQUEST['confirm'])) {
			if($form->Validate()) {
                $fieldColumns = array();

				for($i=0; $i<count($csv->FieldNames); $i++){
                    $fieldColumns[$i] = $_REQUEST['field'.$i];
				}

				while($csv->Data) {
					$product = new SupplierProduct();
					$product->Supplier->ID = $form->GetValue('supplier');

					for($i=0; $i<count($csv->FieldNames); $i++) {
						switch($fieldColumns[$i]) {
							case 'quickfind':
								$product->Product->ID = $csv->Data[$i];
								break;
						}
					}

					if(($product->Supplier->ID > 0) && ($product->Product->ID > 0)) {
						if($product->Product->Get()) {
							$product->GetBySupplierProduct();

							for($i=0; $i<count($csv->FieldNames); $i++) {
                                switch($fieldColumns[$i]) {
									case 'sku':
										$product->SKU = $csv->Data[$i];
										break;

									case 'cost':
										$product->Cost = $csv->Data[$i];
										$product->IsUnavailable = ($product->Cost > 0) ? 'N' : 'Y';
										break;
								}
							}

							$product->Reason = 'Bulk import';

							if($product->ID > 0) {
								$product->Update();
							} else {
								$product->Add();
							}
						}
					}

					$csv->Next();
				}
				$csv->Close();

				$file->Delete();

				redirect('Location: ?action=view');
			}
		}

		$page = new Page(sprintf('<a href="%s">Supplier Import Costs</a> &gt; Field Settings for %s', $_SERVER['PHP_SELF'], $file->FileName), 'Please use the form below to match appropriate fields with those in your CSV document.');
		$page->Display("header");

		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('supplier');
		echo $form->GetHTML('file');
		echo $form->GetHTML('delimit');
		echo $form->GetHTML('encl');

		if(!$form->Valid){
			echo $form->GetError();
			echo '<br />';
		}

		$window = new StandardWindow("Fields");
		$webForm = new StandardForm;

		echo $window->Open();
		echo $window->AddHeader(sprintf('Match key fields.', $csv->TotalRows));
		echo $window->OpenContent();
		echo $webForm->Open();

		for($i=0; $i < count($csv->FieldNames); $i++){
			echo $webForm->AddRow($form->GetLabel('field'.$i), $form->GetHTML('field'.$i) . $form->GetIcon('field'.$i));
		}

		echo $webForm->AddRow('', sprintf('<input type="submit" name="continue" value="continue" class="btn" tabindex="%s">', $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();

		echo $form->Close();

		$csv->Close();
	}

	$page->Display("footer");
}

function step1() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'step1', 'alpha_numeric', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('supplier', 'Supplier', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
	$form->AddGroup('supplier', 'N', 'Standard Suppliers');
	$form->AddOption('supplier', '', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, s.Is_Favourite, IF(c2.Contact_ID IS NULL, CONCAT_WS(' ', p.Name_First, p.Name_Last), CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')'))) AS Supplier FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY Supplier ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier'], $data->Row['Is_Favourite']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('csvFile', 'CSV File', 'file', '', 'file', 3, 255);
	$form->AddField('delimit', 'Values Separated by', 'text', ',', 'paragraph', 1, 1);
	$form->AddField('encl', 'Values Enclosed in', 'text', '"', 'paragraph', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$file = new IFile(null, './temp');
			$file->OnConflict = 'makeunique';
			$file->Extensions = "csv";

			if($file->Upload('csvFile')){
				redirect(sprintf("Location: ?action=step2&supplier=%d&file=%s&delimit=%s&encl=%s", $form->GetValue("supplier"), $file->FileName, $form->GetValue("delimit"), stripslashes($form->GetValue("encl"))));
			} else {
				$errors = $file->GetError();

				for($i=0; $i<count($errors); $i++) {
					$form->AddError($errors[$i]);
				}
			}
		}
	}

    $page = new Page('Supplier Import Costs', 'Import your supplier costs here.');
	$page->Display("header");

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Select a CSV File");
	$webForm = new StandardForm;

	echo $window->Open();
	echo $window->AddHeader('Please select a CSV file from your PC using the file browser field below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier') . $form->GetIcon('supplier'));
	echo $webForm->AddRow($form->GetLabel('csvFile'), $form->GetHTML('csvFile') . $form->GetIcon('csvFile'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader("The following fields are optional. They have been set to default values for your convenience.");
	echo $window->OpenContent();

	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('delimit'), $form->GetHTML('delimit') . $form->GetIcon('delimit'));
	echo $webForm->AddRow($form->GetLabel('encl'), $form->GetHTML('encl') . $form->GetIcon('encl'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="continue" value="continue" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display("footer");
}