<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Sage Integration';
$cron->scriptFileName = 'integrate_sage.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/IntegrationSage.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/TaxCode.php');

$connectionLbuk = new MySQLConnection($GLOBALS['SYNC_DB_HOST'][0], $GLOBALS['SYNC_DB_NAME'][0], $GLOBALS['SYNC_DB_USERNAME'][0], $GLOBALS['SYNC_DB_PASSWORD'][0]);

$data = new DataQuery(sprintf("SELECT Integration_Sage_ID FROM integration_sage WHERE (Type LIKE 'Import' OR Type LIKE 'Confirmation') AND Is_Synchronised='N' ORDER BY Created_On ASC"));
while($data->Row) {
	$integration = new IntegrationSage($data->Row['Integration_Sage_ID']);

	$feed = sprintf('%sremote/sage/feeds/%s', $GLOBALS['DATA_DIR_FS'], $integration->DataFeed);

	if(file_exists($feed)) {
		$cipher = new Cipher(file_get_contents($feed));
		$cipher->Decrypt();

		$importData = preg_replace('/<Company.*?>/', '<Company>', $cipher->Value, 1);
		$importData = xml2array($importData);

		if(isset($importData['Company'][0])) {
			switch($integration->Type) {
				case 'Import':
					if(isset($importData['Company'][0]['TaxRates'][0])) {
						if(isset($importData['Company'][0]['TaxRates'][0]['TaxRate'])) {
							$taxCode = new TaxCode();

							foreach($importData['Company'][0]['TaxRates'][0]['TaxRate'] as $dataItem) {
								$taxCode->IntegrationReference = $dataItem['Reference'];

								if($taxCode->GetByIntegrationReference()) {
									$taxCode->Description = $dataItem['Description'];
									$taxCode->Rate = $dataItem['Rate'];
									$taxCode->Update();

									new DataQuery(sprintf("UPDATE tax SET Tax_Rate=%f WHERE Tax_Code_ID=%d", mysql_real_escape_string($taxCode->Rate), $taxCode->ID));

									$cron->log(sprintf('Entity: Updated, Tax ID: #%d, Tax Code Reference: %s.', $taxCode->ID, $taxCode->IntegrationReference), Cron::LOG_LEVEL_INFO);
								} else {
									$taxCode->Description = $dataItem['Description'];
									$taxCode->Rate = $dataItem['Rate'];
									$taxCode->Add();

									$cron->log(sprintf('Entity: Added, Tax ID: #%d, Tax Code Reference: %s.', $taxCode->ID, $taxCode->IntegrationReference), Cron::LOG_LEVEL_INFO);
								}
							}
						}
					}
						
					$integration->IsSynchronised = 'Y';
					$integration->Update();

					break;

				case 'Confirmation':
					if(isset($importData['Company'][0]['Customers'][0])) {
						if(isset($importData['Company'][0]['Customers'][0]['Customer'])) {
							foreach($importData['Company'][0]['Customers'][0]['Customer'] as $dataItem) {
								$id = $dataItem['Id'];
								$integrationId = $dataItem['AccountReference'];

								$data2 = new DataQuery(sprintf("SELECT COUNT(Contact_ID) AS Count FROM contact WHERE Contact_ID=%d", $id));
								if($data2->Row['Count'] > 0) {
									new DataQuery(sprintf("UPDATE contact SET Integration_Reference='%s' WHERE Contact_ID=%d", $integrationId, $id));

									$cron->log(sprintf('Entity: Updated, Contact ID: #%d, Account Reference: %s.', $id, $integrationId), Cron::LOG_LEVEL_INFO);
								} else {
									$cron->log(sprintf('Entity: Not Found, Contact ID: #%d, Account Reference: %s.', $id, $integrationId), Cron::LOG_LEVEL_WARNING);
								}
								$data2->Disconnect();
							}
						}
					}

					if(isset($importData['Company'][0]['Invoices'][0])) {
						if(isset($importData['Company'][0]['Invoices'][0]['Invoice'])) {
							foreach($importData['Company'][0]['Invoices'][0]['Invoice'] as $dataItem) {
								$id = $dataItem['Id'];
								$integrationId = $dataItem['InvoiceNumber'];

								$item = explode('-', $id);

								if(count($item) > 1) {
									switch(strtolower($item[0])) {
										case 'orderinvoice':
										case 'batchinvoice':
										case 'batchinvoicenonuk':
										case 'batchinvoice_lbuk':
											$connection = $GLOBALS['DBCONNECTION'];
											
											$sub = explode('_', $item[0]);
											
											if(count($sub) == 2) {
												switch($sub[1]) {
													case 'lbuk':
														$connection = $connectionLbuk;
														break;
												}
											}
										
											new DataQuery(sprintf("UPDATE invoice SET Integration_ID='' WHERE Integration_ID=%d", $integrationId), $connection);

											$data2 = new DataQuery(sprintf("SELECT COUNT(Invoice_ID) AS Count FROM invoice WHERE Integration_Reference LIKE '%s'", $id), $connection);
											if($data2->Row['Count'] > 0) {
												new DataQuery(sprintf("UPDATE invoice SET Integration_ID='%s', Is_Paid='Y' WHERE Integration_Reference LIKE '%s'", $integrationId, $id), $connection);

												$cron->log(sprintf('Entity: Invoice Updated, Integration Reference: %s, Integration ID: #%d.', $id, $integrationId), Cron::LOG_LEVEL_INFO);
											} else {
												$cron->log(sprintf('Entity: Invoice Not Found, Integration Reference: %s, Integration ID: #%d.', $id, $integrationId), Cron::LOG_LEVEL_WARNING);
											}
											$data2->Disconnect();
											
											break;

	                                    case 'ordercredit':
										case 'batchcredit':
										case 'batchcreditnonuk':
										case 'batchcredit_lbuk':
											$connection = $GLOBALS['DBCONNECTION'];
											
											$sub = explode('_', $item[0]);
											
											if(count($sub) == 2) {
												switch($sub[1]) {
													case 'lbuk':
														$connection = $connectionLbuk;
														break;
												}
											}
											
											new DataQuery(sprintf("UPDATE credit_note SET Integration_ID='' WHERE Integration_ID=%d", $integrationId), $connection);

											$data2 = new DataQuery(sprintf("SELECT COUNT(Credit_Note_ID) AS Count FROM credit_note WHERE Integration_Reference LIKE '%s'", $id), $connection);
											if($data2->Row['Count'] > 0) {
												new DataQuery(sprintf("UPDATE credit_note SET Integration_ID='%s' WHERE Integration_Reference LIKE '%s'", $integrationId, $id), $connection);

												$cron->log(sprintf('Entity: Credit Updated, Integration Reference: %s, Integration ID: #%d.', $id, $integrationId), Cron::LOG_LEVEL_INFO);
											} else {
												$cron->log(sprintf('Entity: Credit Not Found, Integration Reference: %s, Integration ID: #%d.', $id, $integrationId), Cron::LOG_LEVEL_WARNING);
											}
											$data2->Disconnect();
											
											break;
									}
								}
							}
						}
					}
					
					$integration->IsSynchronised = 'Y';
					$integration->Update();
					
					break;
			}
		}
	} else {
		$cron->log(sprintf('Could not find data feed \'%s\'.', $integration->DataFeed), Cron::LOG_LEVEL_ERROR);
	}

	$data->Next();
}
$data->Disconnect();
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();