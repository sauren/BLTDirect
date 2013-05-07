<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	
if($action == 'report') {
    $session->Secure(2);
	report();
	exit();
} elseif($action == 'search') {
    $session->Secure(2);
	search();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function search() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'search', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('contactid', 'Contact ID', 'text', '', 'numeric_unsigned', 1, 11, false);
	$form->AddField('fname', 'First Name', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('lname', 'Last Name', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('org', 'Organisation', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('postcode', 'Postcode', 'text', '', 'anything', 1, 32, false);

	$sqlSelect = '';
	$sqlFrom = '';
	$sqlWhere = '';

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$sqlSelect = sprintf("SELECT cu.Customer_ID, c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name, o.Org_Name, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS Account_Manager ");
			$sqlFrom = sprintf("FROM contact AS c INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN users AS u ON u.User_ID=c.Account_Manager_ID LEFT JOIN person AS p2 ON p2.Person_ID=u.Person_ID ");
			$sqlWhere = sprintf("WHERE c.Contact_Type='I'");

			if(strlen($form->GetValue('contactid')) > 0) {
				$sqlWhere .= sprintf(" AND c.Contact_ID=%d", $form->GetValue('contactid'));
			}
			
			if(strlen($form->GetValue('fname')) > 0) {
				$sqlWhere .= sprintf(" AND p.Name_First_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $form->GetValue('fname'))));
			}

			if(strlen($form->GetValue('lname')) > 0) {
				$sqlWhere .= sprintf(" AND p.Name_Last_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $form->GetValue('lname'))));
			}

			if(strlen($form->GetValue('org')) > 0) {
				$sqlWhere .= sprintf(" AND o.Org_Name_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $form->GetValue('org'))));
			}

			if(strlen($form->GetValue('postcode')) > 0) {
				$sqlFrom .= sprintf("LEFT JOIN address AS a ON a.Address_ID=p.Address_ID ");
				$sqlWhere .= sprintf(" AND a.Zip_Search LIKE '%s%%'", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $form->GetValue('postcode'))));
			}
		} else {
			echo $form->GetError();
			echo '<br />';
		}
	}

	$page = new Page('Group Order Markup Report', 'Search for an optional customer.');
	$page->Display('header');

	if(isset($_REQUEST['confirm'])) {
		echo '<table width="100%"><tr><td valign="top">';
	}

	$window = new StandardWindow("Search for a Customer.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Search for contacts by any of the below fields.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('contactid'), $form->GetHTML('contactid'));
	echo $webForm->AddRow($form->GetLabel('fname'), $form->GetHTML('fname'));
	echo $webForm->AddRow($form->GetLabel('lname'), $form->GetHTML('lname'));
	echo $webForm->AddRow($form->GetLabel('org'), $form->GetHTML('org'));
	echo $webForm->AddRow($form->GetLabel('postcode'), $form->GetHTML('postcode'));
	echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	if(isset($_REQUEST['confirm'])) {
		echo '</td><td width="10"></td><td valign="top">';

		$table = new DataTable('records');
		$table->SetExtractVars();
		$table->SetSQL(sprintf("%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere));
		$table->SetTotalRowSQL(sprintf("SELECT COUNT(*) AS TotalRows %s%s", $sqlFrom, $sqlWhere));
		$table->AddField('ID#', 'Contact_ID', 'left');
		$table->AddField('Organisation', 'Org_Name', 'left');
		$table->AddField('Name', 'Contact_Name', 'left');
		$table->AddField('Account Manager', 'Account_Manager', 'left');
		$table->AddLink("?action=start&customerid=%s","<img src=\"images/button-tick.gif\" alt=\"Select Customer\" border=\"0\">","Customer_ID");
		$table->AddLink("contact_profile.php?cid=%s","<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Contact_ID");
		$table->Order = "DESC";
		$table->Finalise();
		$table->DisplayTable();
		echo '<br />';
		$table->DisplayNavigation();

		echo '</td></tr></table>';
	}
}

function start(){
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('customerid', 'Customer ID', 'hidden', '0', 'numeric_unsigned', 1, 11, false);
	$form->AddField('group', 'Group', 'select', '0', 'numeric_unsigned', 1, 11, false);
	$form->AddOption('group', '0', '');

	$data = new DataQuery(sprintf("SELECT Contact_Group_ID, Name FROM contact_group ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('group', $data->Row['Contact_Group_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if(($form->GetValue('customerid') == 0) && ($form->GetValue('group') == 0)) {
			$form->AddError('Please select one of the available fields.');
		}
		
		if($form->Validate()){
			redirectTo(sprintf('?action=report&group=%d&customerid=%d', $form->GetValue('group'), $form->GetValue('customerid')));
		}
	}
	
	$customer = new Customer();
	$customer->ID = $form->GetValue('customerid');
	
	if($customer->ID > 0) {
		$customer->Get();
		$customer->Contact->Get();
	}

	$page = new Page('Group Order Markup Report', 'Please select a group for your report.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Group Order Markup.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('customerid');
	
	echo $window->Open();
	echo $window->AddHeader('Select the contract manager and job status filters.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('Customer', (($customer->ID > 0) ? $customer->Contact->Person->GetFullName() : '<em>None</em>') . ' <a href="?action=search"><img src="images/icon_search_1.gif" align="absmiddle" border="0" /></a>');
	echo $webForm->AddRow($form->GetLabel('group'), $form->GetHTML('group'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('customerid', 'Customer', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('group', 'Group', 'hidden', '0', 'numeric_unsigned', 1, 11);
	
	$title = '';
	
	if($form->GetValue('group') > 0) {
		$group = new ContactGroup($form->GetValue('group'));
		
		$title = $group->Name;
		
	} elseif($form->GetValue('customerid') > 0) {
		$customer = new Customer($form->GetValue('customerid'));
		$customer->Contact->Get();
		
		$title = $customer->Contact->Person->GetFullName();
	}
	
	$page = new Page('Group Order Markup Report', '');
	$page->Display('header');
	?>
	
	<br />
	<h3><?php echo $title; ?></h3>
	<p>Listing orders with markup for this group.</p>
	
	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Order ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Organisation</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Owner</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Quantity</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Price</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Cost</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Price</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Markup</strong></td>
		</tr>

		<?php
		if($form->GetValue('group') > 0) {
			$sql = sprintf("SELECT o.Order_ID, o.Billing_Organisation_Name, ol.Product_ID, ol.Product_Title, ol.Quantity, (((ol.Line_Total-ol.Line_Discount)/(ol.Cost*ol.Quantity))*100)-100 AS Markup, ol.Cost*ol.Quantity AS Line_Cost, ol.Line_Total-ol.Line_Discount AS Line_Price, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Owner FROM contact_group AS cg INNER JOIN contact_group_assoc AS cga ON cga.Contact_Group_ID=cg.Contact_Group_ID INNER JOIN customer AS cu ON cu.Contact_ID=cga.Contact_ID INNER JOIN orders AS o ON o.Customer_ID=cu.Customer_ID INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Cost>0 AND ol.Line_Total>0 LEFT JOIN users AS u ON u.User_ID=o.Owned_By LEFT JOIN person AS p ON p.Person_ID=u.Person_ID WHERE cg.Contact_Group_ID=%d ORDER BY o.Order_ID ASC", mysql_real_escape_string($form->GetValue('group')));
			
		} elseif($form->GetValue('customerid') > 0) {
			$sql = sprintf("SELECT o.Order_ID, o.Billing_Organisation_Name, ol.Product_ID, ol.Product_Title, ol.Quantity, (((ol.Line_Total-ol.Line_Discount)/(ol.Cost*ol.Quantity))*100)-100 AS Markup, ol.Cost*ol.Quantity AS Line_Cost, ol.Line_Total-ol.Line_Discount AS Line_Price, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Owner FROM customer AS cu INNER JOIN orders AS o ON o.Customer_ID=cu.Customer_ID INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Cost>0 AND ol.Line_Total>0 LEFT JOIN users AS u ON u.User_ID=o.Owned_By LEFT JOIN person AS p ON p.Person_ID=u.Person_ID WHERE cu.Customer_ID=%d ORDER BY o.Order_ID ASC", mysql_real_escape_string($form->GetValue('customerid')));
		}
		
		$data = new DataQuery($sql);
		if($data->TotalRows > 0) {
			while($data->Row) {
				?>
				
					<td><?php echo $data->Row['Order_ID']; ?></td>
					<td><?php echo $data->Row['Billing_Organisation_Name']; ?></td>
					<td><?php echo $data->Row['Owner']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo $data->Row['Product_ID']; ?></a></td>
					<td><?php echo strip_tags($data->Row['Product_Title']); ?></td>
					<td><?php echo $data->Row['Quantity']; ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Line_Cost']/$data->Row['Quantity'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Line_Price']/$data->Row['Quantity'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Line_Cost'], 2, '.', ','); ?></td>
					<td align="right">&pound;<?php echo number_format($data->Row['Line_Price'], 2, '.', ','); ?></td>
					<td align="right"><?php echo number_format($data->Row['Markup'], 2, '.', ','); ?>%</td>
				</tr>
				
				<?php		
				$data->Next();	
			}
		} else {
			?>
			
			<tr class="dataRow">
				<td align="center" colspan="11">There are no items available for viewing.</td>
			</tr>
			
			<?php
		}
		$data->Disconnect();
		?>
		
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_header.php');
}