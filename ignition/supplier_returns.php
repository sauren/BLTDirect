<?php
require_once('lib/common/app_header.php');

if($action == 'report') {
	$session->Secure(3);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Supplier Returns', 'Please choose a supplier for your report.');
	$page->Display('header');
	?>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Supplier</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Items</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" align="right"><strong>Total</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" width="1%">&nbsp;</td>
		</tr>
	
		<?php
		$data = new DataQuery(sprintf("SELECT COUNT(sr.Supplier_Return_ID) AS Items, SUM(sr.Cost*sr.Quantity) AS Total, s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier FROM supplier_return AS sr INNER JOIN supplier AS s ON s.Supplier_ID=sr.Supplier_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID GROUP BY sr.Supplier_ID ORDER BY Supplier ASC"));
		if($data->TotalRows > 0) {
			while($data->Row) {
				?>
				
				<tr class="dataRow">
					<td><?php echo $data->Row['Supplier']; ?></td>
					<td><?php echo $data->Row['Items']; ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
					<td><a href="?action=report&supplier=<?php echo $data->Row['Supplier_ID']; ?>"><img src="images/folderopen.gif" border="0" /></a></td>
				</tr>
					
				<?php
				$data->Next();	
			}
		} else {
			?>
			
			<tr>
				<td align="center" colspan="4">There are no items available for viewing.</td>
			</tr>
			
			<?php	
		}
		$data->Disconnect();
		?>
		
	</table>
	
	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitLine.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

	$supplierId = isset($_REQUEST['supplier']) ? $_REQUEST['supplier'] : 0;

	$suppliers = array();
	$lines = array();

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID"));
	while($data->Row) {
		$suppliers[$data->Row['Supplier_ID']] = array('Supplier' => new Supplier($data->Row['Supplier_ID']), 'Name' => $data->Row['Supplier']);

		$data->Next();
	}
	$data->Disconnect();

	$sqlWhere = '';

	if($supplierId > 0) {
		$sqlWhere = sprintf(" WHERE sr.Supplier_ID=%d", mysql_real_escape_string($supplierId));
	}

	$totalCost = 0;

	$data = new DataQuery(sprintf("SELECT sr.*, p.Product_Title FROM supplier_return AS sr INNER JOIN product AS p ON p.Product_ID=sr.Product_ID %s", $sqlWhere));
	while($data->Row) {
		$lines[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true')) {
		if($form->Validate()) {
			$debitLines = array();

			for($i=0; $i<count($lines); $i++) {
				$debitLine = new DebitLine();
				$debitLine->Description = $lines[$i]['Product_Title'];
				$debitLine->Quantity = $lines[$i]['Quantity'];
				$debitLine->Product->ID = $lines[$i]['Product_ID'];
				$debitLine->Cost = $lines[$i]['Cost'];
				$debitLine->Total = number_format(($debitLine->Cost * $debitLine->Quantity), 2, '.', '');
				$debitLine->Reason = 'Customer Identified Fault';

				if(!isset($debitLines[$lines[$i]['Supplier_ID']])) {
					$debitLines[$lines[$i]['Supplier_ID']] = array();
				}

				$debitLines[$lines[$i]['Supplier_ID']][] = $debitLine;
			}

			foreach($debitLines as $supplierId=>$lineArr) {
				$suppliers[$supplierId]['Supplier']->Contact->Get();

				$debitTotal = 0;

				for($i=0; $i<count($lineArr); $i++) {
					$debitTotal += $lineArr[$i]->Total;
				}

				$debit = new Debit();
				$debit->Supplier->ID = $supplierId;
				$debit->Total = $debitTotal;
				$debit->IsPaid = 'N';
				$debit->Person = $suppliers[$supplierId]['Supplier']->Contact->Person;
				$debit->Organisation = $suppliers[$supplierId]['Supplier']->Contact->Parent->Organisation->Name;
				$debit->Status = 'Active';
				$debit->Add();

				for($i=0; $i<count($lineArr); $i++) {
					$lineArr[$i]->DebitID = $debit->ID;
					$lineArr[$i]->Add();
				}
			}

			for($i=0; $i<count($lines); $i++) {
				new DataQuery(sprintf("DELETE FROM supplier_return WHERE Supplier_Return_ID=%d", mysql_real_escape_string($lines[$i]['Supplier_Return_ID'])));
			}

			if(count($debitLines) > 1) {
				redirect(sprintf("Location: debit_awaiting_payment.php"));
			} else {
				redirect(sprintf("Location: debit_awaiting_payment.php?action=open&id=%d", $debit->ID));
			}
		}
	}

	$page = new Page('Supplier Returns', 'Listing supplier return lines for debitting.');
	$page->Display('header');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	if(count($lines) > 0) {
		?>

		<table width="100%" border="0">
			<tr>
				<td style="border-bottom: 1px solid #aaaaaa;"><strong>Supplier</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;"><strong>Product</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;"><strong>Quantity</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;"><strong>Purchased</strong></td>
				<td style="border-bottom: 1px solid #aaaaaa;"><strong>Order ID</strong></td>
				<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Cost</strong></td>
				<td align="right" style="border-bottom: 1px solid #aaaaaa;"><strong>Line Cost</strong></td>
			</tr>

			<?php
			foreach($lines as $line) {
				$totalCost += $line['Cost'] * $line['Quantity'];
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $suppliers[$line['Supplier_ID']]['Name']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $line['Product_ID']; ?>"><?php echo $line['Product_ID']; ?></a></td>
					<td><?php echo $line['Quantity']; ?></td>
					<td><?php echo cDatetime($line['Purchased_On'], 'shortdatetime'); ?></td>
					<td><a href="order_details.php?orderid=<?php echo $line['Order_ID']; ?>"><?php echo $line['Order_ID']; ?></a></td>
					<td align="right">&pound;<?php echo number_format($line['Cost'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($line['Cost'] * $line['Quantity'], 2, '.', ','); ?></td>
				</tr>

				<?php
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
			</tr>
		</table><br />

		<input type="submit" value="debit" name="debit" class="btn" />

		<?php
	} else {
		echo '<p><strong>There are no supplier returns for debitting.</strong></p>';
	}

	echo $form->Close();

	$page->Display('footer');
}
?>