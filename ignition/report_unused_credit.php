<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('frame', 'Time frame (days)', 'select', 'none', 'numeric_unsigned', 1, 11);
	$form->AddOption('frame', '', '-- Select --');
	$form->AddOption('frame', '90', '90+');
	$form->AddOption('frame', '120', '120+');
	$form->AddField('orders', 'Minimum orders', 'select', 'none', 'numeric_unsigned', 1, 11);
	$form->AddOption('orders', '', '-- Select --');

	for($i=0;$i<10;$i++) {
		$form->AddOption('orders', $i+1, sprintf('%d+', $i+1));
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			report($form->GetValue('frame'), $form->GetValue('orders'));
			exit;
		}
	}

	$page = new Page('Unused Credit Account Report', 'Please choose a time frame for your report.');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Unused Credit Accounts.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select a time frame for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('frame'), $form->GetHTML('frame'));
	echo $webForm->AddRow($form->GetLabel('orders'), $form->GetHTML('orders'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($frame, $orders){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Unused Credit Account Report', '');
	$page->Display('header');

	$customers = array();

	$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_customer SELECT c.Customer_ID FROM customer AS c INNER JOIN orders AS o ON o.Customer_ID=c.Customer_ID INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE c.Is_Credit_Active='Y' AND o.Created_On>ADDDATE(NOW(), INTERVAL -%d DAY) ORDER BY o.Created_On DESC", mysql_real_escape_string($frame)));
	$data->Disconnect();

	$data = new DataQuery(sprintf("CREATE INDEX Customer_ID ON temp_customer (Customer_ID)"));
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT o.Created_On, o.Order_ID, n.Contact_ID, c.Customer_ID, c.Credit_Limit, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o2.Org_Name, ')')) AS Contact_Name FROM customer AS c INNER JOIN orders AS o ON o.Customer_ID=c.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID LEFT JOIN person AS p ON p.Person_ID=n.Person_ID LEFT JOIN contact AS n2 ON n.Parent_Contact_ID=n2.Contact_ID LEFT JOIN organisation AS o2 ON o2.Org_ID=n2.Org_ID LEFT JOIN temp_customer AS tc ON c.Customer_ID=tc.Customer_ID INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE c.Is_Credit_Active='Y' AND tc.Customer_ID IS NULL ORDER BY o.Created_On DESC", mysql_real_escape_string($frame)));
	while($data->Row){
		if(!isset($customers[$data->Row['Customer_ID']])) {
			$customers[$data->Row['Customer_ID']]['Data'] = $data->Row;
			$customers[$data->Row['Customer_ID']]['Orders'] = 0;
		}

		$customers[$data->Row['Customer_ID']]['Orders']++;

		$data->Next();
	}
	$data->Disconnect();
	?>

	<br />
	<h3>Unused Credit Accounts</h3>
	<p>All customers who have neglected to use their credit accounts for <?php echo $frame; ?> days.</p>

	<table width="100%" border="0">
		<tr>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Contact ID</strong></td>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Contact</strong></td>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Credit Limit</strong></td>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Orders Placed</strong></td>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Last Ordered</strong></td>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Last Order</strong></td>
		</tr>

		<?php
		foreach($customers as $key=>$customer) {
			if($customer['Orders'] >= $orders) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><a href="contact_profile.php?cid=<?php echo $customer['Data']['Contact_ID']; ?>"><?php echo $customer['Data']['Contact_ID']; ?></a></td>
					<td><?php echo $customer['Data']['Contact_Name']; ?></td>
					<td>&pound;<?php echo number_format($customer['Data']['Credit_Limit'], 2, '.', ','); ?></td>
					<td><?php echo $customer['Orders']; ?></td>
					<td><?php echo cDatetime($customer['Data']['Created_On'], 'shortdatetime'); ?></td>
					<td><a href="order_details.php?orderid=<?php echo $customer['Data']['Order_ID']; ?>"><?php echo $customer['Data']['Order_ID']; ?></a></td>
				</tr>

				<?php
			}
		}
		?>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>