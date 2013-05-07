<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TelePrompt.php');

$session->Secure(2);

$cart = new Cart($session, true);

if(!empty($cart->Customer->ID)){
	if(!empty($cart->ShipTo)){
		redirect('Location: order_summary.php');
	} else {
		redirect('Location: order_shipping.php');
	}
}

if($action == 'find'){
	find();
	exit();
} elseif($action == 'use'){
	useCid();
	exit();
} else {
	start();
	exit();
}

function useCid(){
	$cid = $_REQUEST['cid'];
	global $session;
	$cart = new Cart($session, true);
	$cart->Customer->ID = $cid;
	$cart->Update();
	redirect('Location: order_shipping.php');
}

function find(){
	$sqlSelect = sprintf("SELECT cu.Customer_ID, cu.Username, c.Account_Manager_ID, c.Contact_ID, c.Parent_Contact_ID, o.Org_ID, o.Org_Name, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name, a.Address_Line_1, a.Address_Line_2, a.Address_Line_3, a.City, a.Zip, r.Region_Name, n.Country ");
	$sqlFrom = sprintf("FROM customer AS cu INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID INNER JOIN address AS a ON a.Address_ID=p.Address_ID LEFT JOIN regions AS r ON r.Region_ID=a.Region_ID LEFT JOIN countries AS n ON n.Country_ID=a.Country_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ");
	$sqlWhere = sprintf("WHERE TRUE ");
	$sqlMisc = sprintf("ORDER BY o.Org_Name ASC, Name ASC");
	
	if(!empty($_REQUEST['postcode'])) {
		$sqlWhere .= sprintf("AND a.Zip_Search LIKE '%s%%' ", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $_REQUEST['postcode'])));
	} else {
		if(!empty($_REQUEST['orgname'])) {
			$sqlWhere .= sprintf("AND o.Org_Name_Search LIKE '%s%%' ", mysql_real_escape_string(preg_replace('/[^a-zA-Z0-9]/', '', $_REQUEST['orgname'])));
		} else {
			if(!empty($_REQUEST['fname'])) {
				$sqlWhere .= sprintf("AND p.Name_First_Search LIKE '%s%%' ", mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $_REQUEST['fname'])));
			}
			
			if(!empty($_REQUEST['lname'])) {
				$sqlWhere .= sprintf("AND p.Name_Last_Search LIKE '%s%%' ", mysql_real_escape_string(preg_replace('/[^a-zA-Z\p{L}\.\'\s\&\-\\\\\/\-]/u', '', $_REQUEST['lname'])));
			}
		}
	}

	$data19 = new DataQuery($sqlSelect.$sqlFrom.$sqlWhere.$sqlMisc);
	if($data19->TotalRows > 0) {
		$page = new Page('Create a New Order Manually', '');
		$page->Display('header');
		?>
				
	<table width="100%" border="0">
  <tr>
    <td width="250" valign="top"><?php include('order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top">
    
    	<?php
	    $prompt = new TelePrompt();
		$prompt->Output('orderselectcustomerlist');
		
		echo $prompt->Body;
		?>
	
    	<input type="button" name="new registration" value="new registration" class="btn" onclick="window.location.href='order_register.php<?php echo sprintf('?fname=%s&lname=%s&postcode=%s&businesspostcode=%s&name=%s', $_REQUEST['fname'], $_REQUEST['lname'], $_REQUEST['postcode'], $_REQUEST['postcode'], $_REQUEST['orgname']); ?>'" />
		<input type="button" name="refresh" value="refresh results" class="btn" onclick="window.location.href = '?<?php echo $_SERVER['QUERY_STRING']; ?>';" />
		<br /><br />
		
<?php
echo sprintf("<strong>There are %s possible matches</strong> to the details entered. Please select from below or create a new registration. When selecting from below be sure to confirm name, address and username information.", $data19->TotalRows);
?>
	<br />
	<br />
	
<?php
$items = array();

while($data19->Row) {
	if(!isset($items[$data19->Row['Org_ID'].':'.$data19->Row['Org_Name']])) {
		$items[$data19->Row['Org_ID'].':'.$data19->Row['Org_Name']] = array();
	}
	
	$items[$data19->Row['Org_ID'].':'.$data19->Row['Org_Name']][] = $data19->Row;
	
	$data19->Next();
}

foreach($items as $orgReference=>$item) {
	if(!empty($item[0]['Org_Name'])) {
		?>
		
		<h3><?php echo $item[0]['Org_Name']; ?></h3>
		<br /><br />
		
		<?php
		$data = new DataQuery(sprintf("SELECT cu.Customer_ID, cu.Username, c.Account_Manager_ID, c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name, a.Address_Line_1, a.Address_Line_2, a.Address_Line_3, a.City, a.Zip, r.Region_Name, n.Country FROM customer AS cu INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID AND c.Parent_Contact_ID=%d INNER JOIN person AS p ON p.Person_ID=c.Person_ID INNER JOIN address AS a ON a.Address_ID=p.Address_ID LEFT JOIN regions AS r ON r.Region_ID=a.Region_ID LEFT JOIN countries AS n ON n.Country_ID=a.Country_ID", $item[0]['Parent_Contact_ID']));
		while($data->Row) {
			$found = false;
			
			foreach($item as $data19) {
				if($data19['Customer_ID'] == $data->Row['Customer_ID']) {
					$found = true;
					break;
				}
			}
			
			if(!$found) {
				$item[] = $data->Row;
			}
		
			$data->Next();	
		}
		$data->Disconnect();
	}
	?>
	
	<table class="catProducts">
	
		<?php
		foreach($item as $data19) {
			$user = new User();

			if($data19['Account_Manager_ID'] > 0) {
				$user->ID = $data19['Account_Manager_ID'];
				$user->Get();
			}
			
			$address = array();
			
			if(!empty($data19['Address_Line_1'])) {
				$address[] = $data19['Address_Line_1'];
			}
			if(!empty($data19['Address_Line_2'])) {
				$address[] = $data19['Address_Line_2'];
			}
			if(!empty($data19['Address_Line_3'])) {
				$address[] = $data19['Address_Line_3'];
			}
			if(!empty($data19['City'])) {
				$address[] = $data19['City'];
			}
			if(!empty($data19['Region_Name'])) {
				$address[] = $data19['Region_Name'];
			}
			if(!empty($data19['Zip'])) {
				$address[] = $data19['Zip'];
			}
			if(!empty($data19['Country'])) {
				$address[] = $data19['Country'];
			}
			?>

			<tr>
				<td style="padding:10px;">
					<strong><?php echo $data19['Name']; ?></strong><br />
					<br />
					
					<?php echo $data19['Username']; ?><br />
					<?php echo implode(', ', $address); ?><br />
					
					<span style="color: #093;"><strong><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?></strong></span>
				</td>
				<td align="right">
					<input type="button" name="select" value="select" class="btn" onclick="window.location.href='?action=use&cid=<?php echo $data19['Customer_ID']; ?>';" />
				</td>
			</tr>
			
			<?php
		}
		?>
		
	</table>
	<br />
	
	<?php
}
?>
	</table>

	</td></tr></table>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
	} else {
		redirect(sprintf('Location: order_register.php?fname=%s&lname=%s&postcode=%s&businesspostcode=%s&name=%s&find=true', $_REQUEST['fname'], $_REQUEST['lname'], $_REQUEST['postcode'], $_REQUEST['postcode'], $_REQUEST['orgname']));
	}
	$data19->Disconnect();
}

function start(){
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->Icons['valid'] = '';
	$form->AddField('action', 'Action', 'hidden', 'find', 'alpha', 4, 4);
	$form->SetValue('action', 'find');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('fname', 'First Name', 'text', '', 'paragraph', 1, 60);
	$form->AddField('lname', 'Last Name', 'text', '', 'paragraph', 1, 60);
	$form->AddField('orgname', 'Organisation', 'text', '', 'paragraph', 1, 100);
	$form->AddField('postcode', 'Postcode/Zip', 'text', '', 'paragraph', 1, 10);

	$page = new Page('Create New Order', '');
	$page->SetFocus('fname');
	$page->Display('header');
	?>
	<table width="100%" border="0">
	  <tr>
	    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
	    <td width="20" valign="top">&nbsp;</td>
	    <td valign="top">
	    
	    <?php
	    $prompt = new TelePrompt();
		$prompt->Output('orderselectcustomer');
		
		echo $prompt->Body;
		?>
	    <p><strong>First Name:<br /></strong>
		<?php
		echo $form->Open();
		echo $form->GetHtml('action');
		echo $form->GetHtml('confirm');
		echo $form->GetHtml('fname');
		?>
		<?php echo $form->GetIcon('fname'); ?></p>

		<p><strong>Last Name:</strong><br />
		<?php echo $form->GetHtml('lname'); ?>
		<?php echo $form->GetIcon('lname'); ?></p>
		
		<p><strong>Organisation:</strong><br />
		<?php echo $form->GetHtml('orgname'); ?>
		<?php echo $form->GetIcon('orgname'); ?></p>

		<p><strong>Postcode:</strong><br />
		<?php echo $form->GetHtml('postcode'); ?>
		<?php echo $form->GetIcon('postcode'); ?></p>

		<input type="submit" name="continue" value="continue" tabindex="<?php echo ++$form->TabIndex; ?>" class="btn" />
		<?php echo $form->Close(); ?>
	    </td>
	  </tr>
	</table>
	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
