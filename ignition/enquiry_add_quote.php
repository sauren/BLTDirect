<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLineQuote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$session->Secure(3);

$customerId = (isset($_REQUEST['customerid'])) ? $_REQUEST['customerid'] : 0;
$enquiryId = (isset($_REQUEST['enquiryid'])) ? $_REQUEST['enquiryid'] : 0;

if($customerId > 0) {
	$customer = new Customer($customerId);
	$customer->Contact->Get();
} else {
	if(isset($_REQUEST['enquirylineid']) && ($_REQUEST['enquirylineid'] > 0)) {
		$enquiryLine = new EnquiryLine($_REQUEST['enquirylineid']);
		$enquiryId = $enquiryLine->Enquiry->ID;
	}

	if($enquiryId == 0) {
		redirect(sprintf("Location: enquiry_search.php"));
	}

	$enquiry = new Enquiry($enquiryId);
	$enquiry->Customer->Get();
	$enquiry->Customer->Contact->Get();
}

$sessionKey = sprintf('new-%s', $session->ID);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('customerid', 'Customer ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('enquiryid', 'Enquiry ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('enquirylineid', 'Enquiry ID', 'hidden', '0', 'numeric_unsigned', 1, 11);

if($action == 'add' && isset($_REQUEST['confirm'])){
	if($form->Validate()){
		if($customerId > 0) {
			if(!isset($_SESSION['Enquiries'])) {
				$_SESSION['Enquiries'] = array();
			}

			if(!isset($_SESSION['Enquiries'][$sessionKey])) {
				$_SESSION['Enquiries'][$sessionKey] = array();
			}

			if(!isset($_SESSION['Enquiries'][$sessionKey]['Quotes'])) {
				$_SESSION['Enquiries'][$sessionKey]['Quotes'] = array();
			}

			foreach($_REQUEST as $key=>$contact) {
				if(stristr(substr($key, 0, 7), 'select_')) {
					$quote = substr($key, 7, strlen($key));

					if(is_numeric($quote)) {
						if(!isset($_SESSION['Enquiries'][$sessionKey]['Quotes'][$quote])) {
							$_SESSION['Enquiries'][$sessionKey]['Quotes'][$quote] = $quote;
						}
					}
				}
			}
		} else {
			if($form->GetValue('enquirylineid') > 0) {
				$lineQuote = new EnquiryLineQuote();
				$lineQuote->EnquiryLineID = $enquiryLine->ID;

				foreach($_REQUEST as $key=>$contact) {
					if(stristr(substr($key, 0, 7), 'select_')) {
						$quote = substr($key, 7, strlen($key));

						if(is_numeric($quote)) {
							$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM enquiry_line_quote WHERE Enquiry_Line_ID=%d AND Quote_ID=%d", mysql_real_escape_string($enquiryLine->ID), mysql_real_escape_string($quote)));
							if($data->Row['Count'] == 0) {
								$lineQuote->Quote->ID = $quote;
								$lineQuote->Add();
							}
							$data->Disconnect();
						}
					}
				}
			} else {
				if(!isset($_SESSION['Enquiries'])) {
					$_SESSION['Enquiries'] = array();
				}

				if(!isset($_SESSION['Enquiries'][$enquiry->ID])) {
					$_SESSION['Enquiries'][$enquiry->ID] = array();
				}

				if(!isset($_SESSION['Enquiries'][$enquiry->ID]['Quotes'])) {
					$_SESSION['Enquiries'][$enquiry->ID]['Quotes'] = array();
				}

				foreach($_REQUEST as $key=>$contact) {
					if(stristr(substr($key, 0, 7), 'select_')) {
						$quote = substr($key, 7, strlen($key));

						if(is_numeric($quote)) {
							if(!isset($_SESSION['Enquiries'][$enquiry->ID]['Quotes'][$quote])) {
								$_SESSION['Enquiries'][$enquiry->ID]['Quotes'][$quote] = $quote;
							}
						}
					}
				}
			}
		}
	}

	if($form->Valid) {
		if($customerId > 0) {
			redirect(sprintf("Location: enquiry_summary.php?customerid=%d", $customerId));
		} else {
			redirect(sprintf("Location: enquiry_details.php?enquiryid=%d", $enquiry->ID));
		}
	}
}

if($customerId > 0) {
	$page = new Page(sprintf('<a href="enquiry_summary.php?customerid=%d">Create New Enquiry</a> &gt; Attach Quote to Enquiry', $customer->ID), 'Check the quotes belonging to this customer which you want to attach to this enquiry.');
	$page->Display('header');
} else {
	$page = new Page(sprintf('<a href="enquiry_details.php?enquiryid=%d">Enquiry Details</a> &gt; Attach Quote to Enquiry Ref: %s%s', $enquiry->ID, $enquiry->GetPrefix(), $enquiry->ID), 'Check the quotes belonging to this customer which you want to attach to this enquiry.');
	$page->Display('header');
}

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('customerid');
echo $form->GetHTML('enquiryid');
echo $form->GetHTML('enquirylineid');

if($customerId > 0) {
	if($customer->Contact->Parent->ID > 0) {
		$sql = sprintf("SELECT q.*, p2.Name_First, p2.Name_Last FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->Parent->ID));
	} else {
		$sql = sprintf("SELECT * FROM quote WHERE Customer_ID=%d", mysql_real_escape_string($customer->ID));
	}
} else {
	if($enquiry->Customer->Contact->Parent->ID > 0) {
		$sql = sprintf("SELECT q.*, p2.Name_First, p2.Name_Last FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($enquiry->Customer->Contact->Parent->ID));
	} else {
		$sql = sprintf("SELECT * FROM quote WHERE Customer_ID=%d", mysql_real_escape_string($enquiry->Customer->ID));
	}
}

$table = new DataTable('results');
$table->SetSQL($sql);
$table->SetMaxRows(25);
$table->SetOrderBy('Created_On');
$table->SetExtractVars();
$table->Finalise();
$table->ExecuteSQL();

$quotes = array();

while($table->Table->Row) {
	$form->AddField('select_'.$table->Table->Row['Quote_ID'], 'Select Quote', 'checkbox', 'N', 'boolean', 1, 1, false);

	$quotes[] = $table->Table->Row;

	$table->Table->Next();
}
?>

<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
	<thead>
		<tr>
			<th><input type="checkbox" name="checkall" id="checkall" onclick="checkUncheckAll(this)" /></th>
			<th nowrap="nowrap" class="dataHeadOrdered">Quote Date</th>

			<?php
			if((($customerId > 0) && ($customer->Contact->Parent->ID > 0)) || ($enquiry->Customer->Contact->Parent->ID > 0)) {
				?>
				<th nowrap="nowrap">First Name</th>
				<th nowrap="nowrap">Last Name</th>
				<?php
			}
			?>

			<th nowrap="nowrap">Quote Prefix</th>
			<th nowrap="nowrap">Quote Number</th>
			<th nowrap="nowrap">Quote Total</th>
			<th nowrap="nowrap">Quote Status</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>

	<?php
	if(count($quotes) > 0) {
		for($i=0;$i<count($quotes);$i++) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="left" width="16"><?php echo $form->GetHTML('select_'.$quotes[$i]['Quote_ID']); ?></td>
				<td class="dataOrdered" align="left"><?php echo $quotes[$i]['Created_On']; ?></td>

				<?php
				if((($customerId > 0) && ($customer->Contact->Parent->ID > 0)) || ($enquiry->Customer->Contact->Parent->ID > 0)) {
					?>
					<td align="left"><?php print $quotes[$i]['Name_First']; ?>&nbsp;</td>
					<td align="left"><?php print $quotes[$i]['Name_Last']; ?>&nbsp;</td>
					<?php
				}
				?>

				<td align="center"><?php print $quotes[$i]['Quote_Prefix']; ?></td>
				<td align="left"><?php print $quotes[$i]['Quote_ID']; ?></td>
				<td align="right"><?php print $quotes[$i]['Total']; ?></td>
				<td align="left"><?php print $quotes[$i]['Status']; ?></td>
				<td nowrap align="center" width="16"><a href="quote_details.php?quoteid=<?php echo $quotes[$i]['Quote_ID']; ?>"><img src="images/folderopen.gif" alt="Open Quote" border="0" /></a></td>
			</tr>

			<?php
		}
	} else {
		if($customerId > 0) {
			$colspan = ($customer->Contact->Parent->ID > 0) ? 9 : 7;
		} else {
			$colspan = ($enquiry->Customer->Contact->Parent->ID > 0) ? 9 : 7;
		}
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left" colspan="<?php echo $colspan; ?>">No Records Found</td>
		</tr>

		<?php
	}
	?>

	</tbody>
</table><br />

<?php
$table->DisplayNavigation();

echo sprintf('<br />');

if($customerId > 0) {
	echo sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href=\'enquiry_summary.php?customerid=%d\'" />&nbsp;', $customer->ID);
} else {
	echo sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href=\'enquiry_details.php?enquiryid=%d\'" />&nbsp;', $enquiry->ID);
}

if(count($quotes) > 0) {
	echo sprintf('<input type="submit" name="attach" value="attach" class="btn" />&nbsp;');
}

echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');
?>