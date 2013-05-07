<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$data = new DataQuery(sprintf("SELECT DATE_FORMAT(ds.DebitedOn, '%%Y-%%m') AS DebitDate, SupplierID FROM debit_store AS ds GROUP BY DebitDate, ds.SupplierID"));
		while($data->Row) {
            $supplier = new Supplier($data->Row['SupplierID']);
			$supplier->Contact->Get();

            $debit = new Debit();
            $debit->Supplier->ID = $supplier->ID;
	        $debit->IsPaid = 'N';
			$debit->Status = 'Active';
			$debit->Person = $supplier->Contact->Person;
			$debit->Organisation = $supplier->Contact->Parent->Organisation->Name;
			$debit->Add();

			$data2 = new DataQuery(sprintf("SELECT * FROM debit_store WHERE DebitedOn>='%s-01 00:00:00' AND DebitedOn<'%s' AND SupplierID=%d", $data->Row['DebitDate'], date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($data->Row['DebitDate'] . '-01 00:00:00')) + 1, 1, date('Y', strtotime($data->Row['DebitDate'] . '-01 00:00:00')))), mysql_real_escape_string($data->Row['SupplierID'])));
			while($data2->Row) {
                $line = new DebitLine();
		        $line->DebitID = $debit->ID;
				$line->Description = $data2->Row['Description'];
				$line->Quantity = $data2->Row['Quantity'];
				$line->Product->ID = $data2->Row['ProductID'];
				$line->SuppliedBy = $supplier->ID;
				$line->Cost = $data2->Row['Cost'];
				$line->Total = $line->Cost * $line->Quantity;
				$line->Add();

				$debit->Total += $line->Total;

				$data2->Next();
			}
			$data2->Disconnect();

            $debit->Update();

			new DataQuery(sprintf("UPDATE debit SET Created_On='%s' WHERE Debit_ID=%d", sprintf('%s-%s 00:00:00', $data->Row['DebitDate'], date('t', mktime(0, 0, 0, substr($data->Row['DebitDate'], 5, 2), 1, substr($data->Row['DebitDate'], 0, 4)))), mysql_real_escape_string($debit->ID)));

			$data->Next();
		}
		$data->Disconnect();

		new DataQuery(sprintf("TRUNCATE TABLE debit_store"));

		redirect('Location: ?action=view');
	}
}

$page = new Page('Debit Store', 'Listing all debits stored for mass conversion.');
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('confirm');

$table = new DataTable('records');
$table->SetSQL('SELECT ds.*, o.Org_Name FROM debit_store AS ds INNER JOIN supplier AS s ON s.Supplier_ID=ds.SupplierID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID');
$table->AddField('ID','DebitStoreID');
$table->AddField('Debited On', 'DebitedOn');
$table->AddField('Supplier', 'Org_Name');
$table->AddField('Description', 'Description');
$table->AddField('Quantity', 'Quantity');
$table->AddField('Cost', 'Cost', 'right');
$table->SetMaxRows(25);
$table->SetOrderBy('DebitedOn');
$table->Finalise();
$table->DisplayTable();
echo '<br/ >';
$table->DisplayNavigation();

echo '<br/ >';
echo '<input type="submit" class="btn" name="convert" value="convert" />';

echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');