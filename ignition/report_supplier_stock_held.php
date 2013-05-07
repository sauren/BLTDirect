<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductHoldRequest.php');

$session->Secure(2);
view();
exit();

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');
	
	$reportCache = new ReportCache();
	
	if(!isset($_REQUEST['id']) || !$reportCache->Get($_REQUEST['id'])) {
		$reportCache->Report->GetByReference('supplierstockheld');
		
		if(!$reportCache->GetMostRecent()) {
			redirect('Location: report.php');
		}
	}
	
	$reportCache->Report->Get();
	
	$data = $reportCache->GetData();
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Report Cache ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	
	for($i=0; $i<count($data); $i++) {
		$form->AddField(sprintf('qty_%d', $i), sprintf('Hold Quantity of \'%s\' for \'%s\'.', $data[$i]['Product_Title'], $data[$i]['Supplier']), 'text', '0', 'numeric_unsigned', 1, 11, true, 'size="5"');
	}
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			new DataQuery(sprintf("DELETE FROM supplier_product_hold_request"));
			
			$request = new SupplierProductHoldRequest();
			
			for($i=0; $i<count($data); $i++) {
				$request->Quantity = $form->GetValue(sprintf('qty_%d', $i));
				
				if($request->Quantity > 0) {
					$request->Supplier->ID = $data[$i]['Supplier_ID'];
					$request->Product->ID = $data[$i]['Product_ID'];
					$request->Add();
				}	
			}
			
			redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $reportCache->ID));
		}
	}
	
	$page = new Page(sprintf('<a href="reports.php">Reports</a> &gt; <a href="reports.php?action=open&id=%d">Open Report</a> &gt; %s', $reportCache->Report->ID, $reportCache->Report->Name), sprintf('Report data for the \'%s\'', cDatetime($reportCache->CreatedOn, 'shortdatetime')));
	$page->Display('header');
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	?>
	
	<br />
	<h3>Stock Held</h3>
	<p>Supplier stock held for selected products and sales statistics for the last 12 months.</p>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;" align="left"><strong>Product ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="left"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="left"><strong>Supplier</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Orders</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Quantity Sold</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Total Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right"><strong>Hold Quantity</strong></td>
		</tr>
	
		<?php
		for($i=0; $i<count($data); $i++) {
			?>
	
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="left"><?php echo $data[$i]['Product_ID']; ?></td>
				<td align="left"><?php echo $data[$i]['Product_Title']; ?></td>
				<td align="left"><?php echo $data[$i]['Supplier']; ?></td>
				<td align="right">&pound;<?php echo number_format($data[$i]['Cost'], 2, '.', ','); ?></td>
				<td align="right"><?php echo $data[$i]['Orders']; ?></td>
				<td align="right"><?php echo $data[$i]['Quantity_Sold']; ?></td>
				<td align="right">&pound;<?php echo number_format($data[$i]['Total_Cost'], 2, '.', ','); ?></td>
				<td align="right"><?php echo $form->GetHTML(sprintf('qty_%d', $i)); ?></td>
			</tr>
	
			<?php
		}
		?>
	
	</table>
	<br />
	
	<input type="submit" class="btn" name="submit" value="submit" />

	<?php
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}