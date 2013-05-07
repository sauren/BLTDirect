<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/HtmlElement.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Form.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Page.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Customer.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataTable.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');

$session->Secure(3);

if(!isset($_REQUEST['oid']) && !isset($_REQUEST['cid']) && !isset($_REQUEST['cname'])){
	$page = new Page('Create New Return',
					 'Create a new return for a customer who wishes to return a product.');
	start();
	$page->Display('header');
	echo find_dialog();
	$page->Display('footer');
	exit;
}

if(isset($_REQUEST['add'])){
	add();
	exit;
}
// User an id by searching through a list.
if(isset($_REQUEST['list']) && $_REQUEST['list'] == 'contact'){
	if(isset($_REQUEST['oid']) && is_numeric($_REQUEST['oid'])){
		findLine($_REQUEST['oid']);
		exit;
	}
	if(isset($_REQUEST['cid']) && is_numeric($_REQUEST['cid'])){
		findOrder($_REQUEST['cid']);
		exit;
	}
	if(isset($_REQUEST['cname']) && is_string($_REQUEST['cname']) || $_REQUEST['findContact_Current'])
		findContact($_REQUEST['cname']);
	exit;
}
// user typed in oid directly.
if(isset($_REQUEST['oid']) && is_numeric($_REQUEST['oid'])){
	findLine($_REQUEST['oid']);
	exit;
}
// user typed in cid directly.
if(isset($_REQUEST['cid']) && is_numeric($_REQUEST['cid'])){
	findOrder($_REQUEST['cid']);
	exit;
}
$customer;
$order;
function start(){
	$form = new Form('find');
	if(isset($_REQUEST['find']) && $_REQUEST['find'] == 'contact'){
		if($_REQUEST['SearchType'] == 'cnamesearch')
			$form->AddField('SearchArea', 'SearchArea', 'text',
				            $_REQUEST['SearchArea'], 'alpha_numeric',
				            1, 255);
		else
			$form->AddField('SearchArea', 'SearchArea', 'text',
				            $_REQUEST['SearchArea'], 'numeric_unsigned',
				            1, 255);
		if($form->Validate()){
			$value = $form->GetValue('SearchArea');
			$qs = '';	// Query String
			switch($_REQUEST['SearchType']){
			case 'cid':         $qs = "cid=$value";                break;
			case 'oid':         $qs = "oid=$value";                break;
			case 'cnamesearch': $qs = "list=contact&cname=$value"; break;
			}
			//debug($qs);
			redirect("Location: return_add.php?$qs");
			exit;
		}
	}
	// Find order by date/ID
	// Find customer by name/ID
}
function find_dialog(){
	$content = new HtmlElementDiv;
	$para = new HtmlElementP(null, null, null, null, true);
	$form = new HtmlElementForm($_SERVER['PHP_SELF'], 'post', null, 'form');
	$searchLbl = new HtmlElementLabel('Search', 'SearchArea');
	$searchTxt = new HtmlElementText('SearchArea', '', '', 'SearchArea');
	$searchSel = new HtmlElementSelect('SearchType', 'SearchType');
	$searchSel->AddOption('cid', 'Contact ID', true);
	$searchSel->AddOption('cnamesearch', 'Contact Name', false);
	$searchSel->AddOption('oid', 'Order ID', false);
	$para->AddChildElement($searchLbl);
	$para->AddChildElement($searchTxt);
	$para->AddChildElement($searchSel);
	$para->AddChildElement(new HtmlElementSubmit('find', 'contact'));
	$form->AddChildElement($para);
	$content->AddChildElement($form);
	return $content->ToString();
}

function findContact($cname){
	// Get table of all contacts matching cid.
	$part = explode(' ', $cname);
	$where = "WHERE p.Name_First LIKE '%$cname%' OR p.Name_Last LIKE '%$cname%'";
	foreach($part as $p){
		$n = trim($p);
		$where .= " OR p.Name_First LIKE '%$n%' OR p.Name_Last LIKE '%$n'";
	}
	$sql = "SELECT * FROM contact c
		    INNER JOIN person p ON c.Person_ID = p.Person_ID
			$where";

	$table = new DataTable("findContact");
	$table->SetSQL($sql);
	$table->AddField('ID#', 'Contact_ID', 'left');
	$table->AddField('Organisation', 'Org_Name', 'left');
	$table->AddField('First Name', 'Name_First', 'left');
	$table->AddField('Last Name', 'Name_Last', 'left');
	$table->AddField('Customer', 'Is_Customer', 'center');
	$table->AddField('Supplier', 'Is_Supplier', 'center');
	$table->AddField('Active', 'Is_Active', 'center');
	$table->AddLink("return_add.php?cid=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update Contact\" border=\"0\">","Contact_ID");
	$table->Order = "desc";
	$table->SetMaxRows(25);
	$table->SetOrderBy("Contact_ID");
	$table->Finalise();
	$page = new Page('Contact Address Book Search','');
	$page->Display('header');
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	find_dialog();
	$page->Display('footer');
}

function findOrder($cid){
	require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Contact.php');
	$contact = new Contact;
	if(!$contact->Get($cid)){
		echo "<p>Contact does not exist. Please try again</p>";
		$_REQUEST['searchType'] = 'cid';
		find_dialog();
		exit;
	}
	$sql = sprintf("SELECT * FROM orders o
			INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID
			INNER JOIN product AS p ON ol.Product_ID=p.Product_ID
			INNER JOIN customer AS cu ON o.Customer_ID=cu.Customer_ID
			INNER JOIN contact AS co ON cu.Contact_ID=co.Contact_ID
			WHERE co.Contact_ID=%d AND ol.Invoice_ID<>0 AND p.Is_Non_Returnable='N'
			GROUP BY o.Order_ID", $cid);

	$table = new DataTable("findOrder");
	$table->SetSQL($sql);
	$table->AddField('ID#', 'Order_ID', 'left');
	$table->AddField('Order Date', 'Ordered_On', 'left');
	$table->AddField('Order Total', 'Total', 'Right');
	$table->AddLink("return_add.php?oid=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update Contact\" border=\"0\">","Order_ID");
	$table->Order = "desc";
	$table->SetMaxRows(25);
	$table->SetOrderBy("Ordered_On");
	$page = new Page("Orders");
	$page->Display('header');
	$table->Finalise();
	$table->DisplayTable();
	$table->DisplayNavigation();
	$page->Display('footer');
}

function findLine($oid, $errors=null){
	$order = new Order;
	if(!$order->Get($oid)){
		echo "<p>Order not found. Please try again</p>\n";
		$_REQUEST['searchType'] = 'oid';
		find_dialog();
		exit;
	}
	$order->GetLines();
	require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ReturnReason.php');
	$reason = new ReturnReason;
	$reason->GetReasons();
	// Build table manually because it contains input fields within one of the
	// middle cols.

	$page = new Page("Lines for Order #$oid");
	$page->Display('header');

	if(is_array($errors))
		echo displayErrors($errors);
	echo '<form action="return_add.php" method="post">'."\n";
	echo '  <input type="hidden" name="add" value="true" />'."\n";
	echo '  <input type="hidden" name="oid" value="'.$oid.'" />'."\n";
	echo '<table align="center" cellpadding="4" cellspacing="0" class="DataTable">'."\n";
	echo "<thead>\n";
	echo "  <tr>\n";
	echo "    <th>Name</th>\n";
	echo "    <th>Quantity</th>\n";
	echo "    <th>Price</th>\n";
	echo "    <th></th>\n";
	echo "  </tr>\n";
	echo "</thead>\n";
	echo "<tbody>\n";
	echo "</tbody>\n";
	
	for($i=0; $i<count($order->Line); $i++) {
		$order->Line[$i]->Product->Get();
		
		if($order->Line[$i]->Product->IsNonReturnable == 'N') {
			echo "  <tr>\n";
			echo "    <td>{$order->Line[$i]->Product->Name}</td>\n";
			echo "    <td><input type=\"text\" name=\"orderLineQty_{$order->Line[$i]->ID}\" size=\"2\" value=\"{$order->Line[$i]->Quantity}\" /> / {$order->Line[$i]->Quantity}</td>\n";
			echo "    <td>{$order->Line[$i]->Price}</td>\n";
			echo "    <td><input type=\"checkbox\" name=\"orderLine_{$order->Line[$i]->ID}\" value=\"orderLine_{$order->Line[$i]->ID}\"/></td>\n";
			echo "  </tr>\n";
		}
	}
	
	echo "</table>\n";
	echo "<br/>\n";
	echo "<strong>Return Reason</strong><br /><select name=\"reason\">\n";
	foreach($reason->Collection as $c){
		echo "  <option value=\"{$c->ID}\">{$c->Title}</option>\n";
	}
	echo "</select>\n";

	echo '<br /><br /><strong>Customer Notes</strong><br /><textarea name="customernotes" rows="7" style="width: 300px;"></textarea>';

	echo "<br/>\n";
	echo "<br/>\n";
	echo '<input type="submit" class="btn" name="add" value="add" />'."\n";
	echo '</form>';

	$page->Display('footer');
}

function add(){
	//TODO: Check qty to return against qty in line.
	$lines = array();
	$qty = array();
	foreach($_REQUEST as $k => $v){
		if(strpos($k, 'orderLine_') !== false){
			$e = explode('_', $k);
			if(is_numeric($e[1]))
				$lines[] = $e[1];	// Put id that was selected by user in array.
		}
		if(strpos($k, 'orderLineQty_') !== false){
			$e = explode('_', $k);
			if(is_numeric($e[1]))
				$qty[$e[1]] = $v;
		}
	}

	$reasonID = 0;
	if(isset($_REQUEST['reason']) && is_numeric($_REQUEST['reason']))
		$reasonID = $_REQUEST['reason'];

	require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');
	require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/OrderLine.php');
	require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Return.php');
	$order = new Order($_REQUEST['oid']);
	$error = array();	//Only contains one element but needs to be array to be passed to findLine().
	$returns = array();
	if(empty($lines)){
		$error[] = "Please select at least one product to return.";
		findLine($order->ID, $error);
		exit;
	} else {
		foreach($lines as $l){
			$line = new OrderLine($l);
			$return = new ProductReturn;
			$return->OrderLine->ID = $l;
			$return->Invoice->ID = $line->InvoiceID;
			$return->Customer->ID = $order->Customer->ID;
			$return->Reason->ID = $reasonID;
			$return->Note = isset($_REQUEST['customernotes']) ? $_REQUEST['customernotes'] : '';
			$return->Quantity = $qty[$l];
			if($return->Quantity > $line->Quantity)
				$error[] = "Please check the quantities to return do not exceed the quantities purchased.";
			$return->RequestedOn = now();
			$returns[] = $return;
		}

		if(empty($error)){
			foreach($returns as $r){
				$r->Add();
			}
		} else {
			findLine($order->ID, $error);
			exit;
		}
	}
	redirect("Location: returns_new.php");
}

function displayErrors($errors){
	require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Bubble.php');
	$html = "<ol>\n";
	foreach($errors as $e)
		$html .= "  <li>$e</li>\n";
	$html .= "</ol>\n";
	$bubble = new Bubble('Please correct the following:',
		$html, '', 'error');
	return $bubble->GetHTML();
}
?>