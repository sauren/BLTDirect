<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "newgroup"){
	$session->Secure(3);
	newGroup();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroup.php');
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$group = new ContactGroup();
		$group->Delete($_REQUEST['id']);
	}
	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroup.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 255, true, 'style="width: 300px;"');
	$form->AddField('description', 'Description', 'textarea', '', 'anything', 1, 1024, false, 'rows="5" style="width: 300px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$group = new ContactGroup();
			$group->Name = $form->GetValue('name');
			$group->Description = $form->GetValue('description');
			$group->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="contact_groups.php">Contact Groups</a> &gt; Add a New Contact Group','Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Group');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'contact_groups.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroup.php');

	$group = new ContactGroup();
	if(!$group->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', $group->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $group->Name, 'anything', 1, 255, true, 'style="width: 300px;"');
	$form->AddField('description', 'Description', 'textarea', $group->Description, 'anything', 1, 1024, false, 'rows="5" style="width: 300px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$group->Name = $form->GetValue('name');
			$group->Description = $form->GetValue('description');
			$group->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="contact_groups.php">Contact Groups</a> &gt; Edit Contact Group','Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Edit Group');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'contact_groups.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function newGroup() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroupAssoc.php');

	$contacts = array();

	foreach($_REQUEST as $key=>$value) {
		if(strlen($key) >= 7) {
			if(substr($key, 0, 7) == 'select_') {
				$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact_group_assoc WHERE Contact_Group_ID=%d", substr($key, 7)));
				while($data->Row) {
					$contacts[$data->Row['Contact_ID']] = $data->Row['Contact_ID'];

					$data->Next();
				}
				$data->Disconnect();
			}
		}
	}

	if(count($contacts) > 0) {
		$group = new ContactGroup();
		$group->Name = 'New Group';
		$group->Add();

		$group->Name = sprintf('New Group %d', $group->ID);
		$group->Update();

		$groupContact = new ContactGroupAssoc();
		$groupContact->ContactGroup->ID = $group->ID;

		foreach($contacts as $contact) {
			$groupContact->Contact->ID = $contact;
			$groupContact->Add();
		}
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$page = new Page('Contact Groups','This area allows you to maintain multiple groups for your contacts.');
	$page->Display('header');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'newgroup', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$groups = array();

	$data = new DataQuery(sprintf("SELECT * FROM contact_group ORDER BY Name ASC"));
	while($data->Row) {
		$groups[] = $data->Row;

		$form->AddField('select_'.$data->Row['Contact_Group_ID'], 'Select Contact', 'checkbox', 'N', 'boolean', 1, 1, false);

		$data->Next();
	}
	$data->Disconnect();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	if(count($groups) > 0) {
		$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Order_Count, SUM(o.SubTotal - o.TotalDiscount) AS Order_Total FROM contact_group_assoc AS ga INNER JOIN contact AS c ON c.Contact_ID=ga.Contact_ID INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID INNER JOIN orders AS o ON o.Customer_ID=cu.Customer_ID WHERE o.Status<>'Cancelled'"));
		$totalOrders = $data->Row['Order_Count'];
		$totalSpend = $data->Row['Order_Total'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Contact_Count FROM contact_group_assoc AS ga"));
		$totalContacts = $data->Row['Contact_Count'];
		$data->Disconnect();
		?>

		<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
			<thead>
				<tr>
					<th nowrap="nowrap">Total Contacts</th>
					<th nowrap="nowrap">Total Order</th>
					<th nowrap="nowrap">Total Net Spend</th>
				</tr>
			</thead>
			<tbody>
				<tr class="dataRow">
					<td align="left"><?php print $totalContacts; ?></td>
					<td align="left"><?php print $totalOrders; ?></td>
					<td align="left">&pound;<?php print number_format($totalSpend, 2, '.', ','); ?></td>
				</tr>
			</tbody>
		</table><br />

		<?php
	}
	?>

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th><input type="checkbox" name="checkall" id="checkall" onclick="checkUncheckAll(this)" /></th>
				<th class="dataHeadOrdered">ID#</th>
				<th>Name</th>
				<th>Description</th>
				<th colspan="4">&nbsp;</th>
			</tr>
		</thead>
		<tbody>

			<?php
			for($i=0;$i<count($groups);$i++) {
			?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td align="left" width="16"><?php echo $form->GetHTML('select_'.$groups[$i]['Contact_Group_ID']); ?></td>
					<td class="dataOrdered" align="left"><?php echo $groups[$i]['Contact_Group_ID']; ?></td>
					<td align="left"><?php echo $groups[$i]['Name']; ?>&nbsp;</td>
					<td align="left"><?php print $groups[$i]['Description']; ?>&nbsp;</td>
					<td align="center" width="16"><a href="javascript:popUrl('contact_group_print.php?gid=<?php echo $groups[$i]['Contact_Group_ID']; ?>', 800, 600);"><img src="images/icon_print_1.gif" alt="Print" border="0" /></a></td>
					<td align="center" width="16"><a href="contact_group_assoc.php?gid=<?php echo $groups[$i]['Contact_Group_ID']; ?>"><img src="images/folderopen.gif" alt="Open Group" border="0" /></a></td>
					<td align="center" width="16"><a href="contact_groups.php?action=update&id=<?php echo $groups[$i]['Contact_Group_ID']; ?>"><img src="images/icon_edit_1.gif" alt="Update Group" border="0" /></a></td>
					<td align="center" width="16"><a href="javascript:confirmRequest('contact_groups.php?action=remove&confirm=true&id=<?php echo $groups[$i]['Contact_Group_ID']; ?>','Are you sure you want to remove this group?');"><img src="images/aztector_6.gif" alt="Remove" border="0" /></a></td>
				</tr>

				<?php
			}
			?>

		</tbody>
	</table><br />

	<input type="submit" name="create from selection" value="create from selection" class="btn" />
	<input type="button" name="add" value="add a new group" class="btn" onclick="window.location.href='contact_groups.php?action=add';" />

	<?php
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}