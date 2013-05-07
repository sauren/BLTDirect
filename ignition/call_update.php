<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CsvImport.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Call.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CallFrom.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CallTo.php');

if($action == 'complete'){
	$session->Secure(2);
	complete();
	exit;
} else {
	$session->Secure(3);
	upload();
	exit;
}

function complete(){
	$page = new Page('Call Update', 'You have successfully uploaded a Call update.');
	$page->Display('header');

	echo sprintf('<p>Click <a href="%s">here</a> to return to the upload facility for further processing.</p>', $_SERVER['PHP_SELF']);

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function upload() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'upload', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('file', 'Call Update', 'file', '', 'file', NULL, NULL);
	$form->AddField('leading', 'Append Leading Zero?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')){
		if($form->Validate()) {
			$file = new IFile();
			$file->Extensions = 'CSV';
			$file->OnConflict = 'overwrite';
			$file->SizeLimit = 20000;
			$file->SetDirectory($GLOBALS['TEMP_DIR_FS']);

			if(isset($_FILES['file']) && !empty($_FILES['file']['name'])){
				if($file->Upload('file')){
					if(strpos($file->FileName, '.') !== false) {
						$name = substr($file->FileName, 0, strpos($file->FileName, '.'));
						$correct = false;

						if(preg_match('/[\w]*_([1][9][0-9][0-9]|[1-2][0][0-9][0-9])_([0][0-9]|[1][0-2])/', $name, $matches)) {
							$correct = true;

							$date = sprintf('%s-%s-01 00:00:00', $matches[1], $matches[2]);

							new DataQuery(sprintf("DELETE FROM `call` WHERE Called_On>='%s' AND Called_On<ADDDATE('%s', INTERVAL 1 MONTH)", mysql_real_escape_string($date), mysql_real_escape_string($date)));

							$csv = new CsvImport(sprintf('%s%s', $GLOBALS['TEMP_DIR_FS'], $file->FileName), ',', '"');
							$csv->HasFieldNames = true;

							if($csv->Open()) {
								while($csv->Data) {
									$callDateStr = '0000-00-00 00:00:00';
									$callFromStr = '';
									$callToStr = '';
									$duration = 0;
									$cost = 0;
									$date = '';
									$time = '';
									$description = '';
									$type = '';

									for($i=0; $i<count($csv->Data); $i++) {
										switch ($i) {
											case 0:
												$callFromStr = sprintf('%s%s', ($form->GetValue('leading') == 'Y') ? '0' : '', $csv->Data[$i]);
												break;
											case 2:
												$date = sprintf('%s-%s-%s', substr($csv->Data[$i], 6, 4), substr($csv->Data[$i], 3, 2), substr($csv->Data[$i], 0, 2));
												break;
											case 3:
												$time = substr($csv->Data[$i], 0, 8);
												break;
											case 4:
												$callToStr = sprintf('%s%s', ($form->GetValue('leading') == 'Y') ? '0' : '', $csv->Data[$i]);
												break;
											case 5:
												$description = $csv->Data[$i];
												break;
											case 6:
												$type = $csv->Data[$i];
												break;
											case 7:
												$durationItems = explode(':', $csv->Data[$i]);

												$duration += ((int) $durationItems[0]) * 60 * 60;
												$duration += ((int) $durationItems[1]) * 60;
												$duration += ((int) $durationItems[2]);

												break;
											case 8:
												$cost = $csv->Data[$i];
												break;
										}
									}

									$callFrom = new CallFrom();

									if(!$callFrom->Exists($callFromStr)) {
										$callFrom->Add();
									}

									$callTo = new CallTo();

									if(!$callTo->Exists($callToStr)) {
										$callTo->Description = $description;
										$callTo->Type = $type;
										$callTo->Add();
									}

									$call = new Call();
									$call->CallFrom->ID = $callFrom->ID;
									$call->CallTo->ID = $callTo->ID;
									$call->Duration = $duration;
									$call->Cost = $cost;
									$call->CalledOn = sprintf('%s %s', $date, $time);
									$call->Add();

									$csv->Next();
								}

								$csv->Close();
							}

							redirect(sprintf("Location: %s?action=complete", $_SERVER['PHP_SELF']));
						}

						if(!$correct) {
							$form->AddError(sprintf('Update file \'%s\' does not match correct format: &lt;name&gt;_&lt;year&gt;_&lt;month&gt;.csv', $file->FileName), 'file');
							$file->Delete();
						}
					} else {
						$form->AddError(sprintf('Update file \'%s\' missing extension.', $file->FileName), 'file');
						$file->Delete();
					}
				} else {
					for($i = 0; $i < count($file->Errors); $i++) {
						$form->AddError($file->Errors[$i], 'file');
					}
				}
			}
		}
	}

	$page = new Page('Call Update', 'Upload a Call (CSV) update file.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	$window = new StandardWindow("Upload update");
	$webForm = new StandardForm();

	echo $window->Open();
	echo $window->AddHeader('Browse for a Call (CSV) update file to upload.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('file'), $form->GetHTML('file'));
	echo $webForm->AddRow($form->GetLabel('leading'), $form->GetHTML('leading'));
	echo $webForm->AddRow('', 'Update file format <strong>&lt;name&gt;_&lt;year&gt;_&lt;month&gt;.csv</strong>, example <strong>BilledCalls_2009_03.csv</strong>');
	echo $webForm->AddRow('', '<input type="submit" name="upload" value="upload" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>