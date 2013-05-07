<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');

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

function step2(){
	require_once("./lib/classes/IFile.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CsvImport.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

	$fields = array('' => '', 'reference' => 'Supplier Reference', 'product' =>  'Product ID', 'cost' => 'Cost');

    $form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'step2', 'alpha_numeric', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
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
			$form->AddField('field'.$i, $csv->FieldNames[$i], 'select', checkField($csv->FieldNames[$i], $fields), 'alpha_numeric', 0, 255);

			foreach($fields as $key=>$value) {
				$form->AddOption('field'.$i, $key, $value);
			}
		}

		if(isset($_REQUEST['confirm'])) {
			if($form->Validate()) {
				$references = array();

				$data = new DataQuery(sprintf("SELECT Supplier_ID, UPPER(Reference) AS Reference FROM supplier WHERE Reference<>''"));
				while($data->Row) {
					$references[$data->Row['Reference']] = $data->Row['Supplier_ID'];

					$data->Next();
				}
				$data->Disconnect();

                $fieldColumns = array();

				for($i=0; $i<count($csv->FieldNames); $i++){
                    $fieldColumns[$i] = $_REQUEST['field'.$i];
				}

				while($csv->Data) {
					$product = new SupplierProduct();

					for($i=0; $i<count($csv->FieldNames); $i++) {
						switch($fieldColumns[$i]) {
							case 'reference':
								$reference = strtoupper($csv->Data[$i]);

								if(isset($references[$reference])) {
									$product->Supplier->ID = $references[$reference];
								}

								break;
							case 'product':
								if(is_numeric($csv->Data[$i])) {
									$product->Product->ID = $csv->Data[$i];
								}

							break;
						}
					}

					if(($product->Supplier->ID > 0) && ($product->Product->ID > 0)) {
						if($product->Product->Get()) {
							if($product->GetBySupplierProduct()) {
	                            for($i=0; $i<count($csv->FieldNames); $i++) {
	                                switch($fieldColumns[$i]) {
										case 'cost':
											if(is_numeric($csv->Data[$i])) {
												$product->Cost = $csv->Data[$i];
											}

											break;
									}
								}

								$product->Update();

							} else {
								for($i=0; $i<count($csv->FieldNames); $i++) {
	                                switch($fieldColumns[$i]) {
										case 'cost':
											if(is_numeric($csv->Data[$i])) {
												$product->Cost = $csv->Data[$i];
											}

											break;
									}
								}

								if($product->Cost > 0) {
									$product->Add();
								}
							}
						}
					}

					$csv->Next();
				}
				$csv->Close();

				$file->Delete();

				redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
			}

		}

		$page = new Page(sprintf('<a href="%s">Product Import Costs</a> &gt; Field Settings for %s', $_SERVER['PHP_SELF'], $file->FileName), 'Please use the form below to match appropriate fields with those in your CSV document.');
		$page->Display("header");

		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
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
		echo $window->AddHeader(sprintf('Please match as many fields as you can. The fields associated with your CSV file are shown to the left. If your file does not have field titles in the first row you will see Field n: as titles. The matches you make here will be used for each of the %s rows in your CSV file.', $csv->TotalRows));
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
	$form->AddField('csvFile', 'CSV File', 'file', '', 'file', 3, 255);
	$form->AddField('delimit', 'Values Separated by', 'text', ',', 'paragraph', 1, 1);
	$form->AddField('encl', 'Values Enclosed in', 'text', '"', 'paragraph', 1, 1);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$file = new IFile(NULL, './temp');
			$file->OnConflict = 'makeunique';
			$file->Extensions = "csv,txt";

			if($file->Upload('csvFile')){
				redirect(sprintf("Location: %s?action=step2&file=%s&delimit=%s&encl=%s", $_SERVER['PHP_SELF'], $file->FileName, $form->GetValue("delimit"), $form->GetValue("encl")));
			} else {
				$errors = $file->GetError();

				for($i=0; $i<count($errors); $i++) {
					$form->AddError($errors[$i]);
				}
			}
		}
	}

    $page = new Page('Product Import Costs', 'Import your supplier costs here.');
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