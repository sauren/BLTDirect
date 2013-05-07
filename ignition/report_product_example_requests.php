<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductImageExampleRequest.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'approve') {
	$session->Secure(3);
	approve();
	exit;
} else {
	$session->Secure(2);
	report();
	exit;
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$request = new ProductImageExampleRequest();
		$request->delete($_REQUEST['id']);
	}

	redirectTo('?action=view');
}

function approve() {
	if(isset($_REQUEST['id'])) {
		$request = new ProductImageExampleRequest($_REQUEST['id']);
		$request->approve();
	}

	redirectTo('?action=view');
}

function report() {
	$examples = array();

	$data = new DataQuery(sprintf("SELECT pier.*, p.Product_Title, CONCAT_WS(' ', pe.Name_First, pe.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Customer_Name FROM product_image_example_request AS pier INNER JOIN product AS p ON p.Product_ID=pier.productId INNER JOIN customer AS cu ON cu.Customer_ID=pier.customerId INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID INNER JOIN person AS pe ON pe.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY pier.createdOn ASC"));
	while($data->Row) {
		$examples[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Product Example Requests Report', '');
	$page->Display('header');
	?>

	<br />
	<h3>Unapproved Product Examples</h3>
	<br />

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Created On</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="left" width="1%" colspan="2">&nbsp;</td>
		</tr>

		<?php
		if(count($examples) > 0) {
			foreach($examples as $exampleItem) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo cDatetime($exampleItem['createdOn'], 'shortdatetime'); ?></td>
					<td><?php echo $exampleItem['Product_Title']; ?></td>
					<td><?php echo $exampleItem['Customer_Name']; ?></td>
					<td style="white-space: nowrap;">
						<a href="product_image_example_request_download.php?id=<?php echo $exampleItem['id']; ?>"><img src="images/folderopen.gif" alt="Download" border="0" /></a>
						<a href="javascript:confirmRequest('?action=approve&id=<?php echo $exampleItem['id']; ?>', 'Are you sure you wish to approve this item?');"><img src="images/aztector_5.gif" alt="Approve" border="0" /></a>
						<a href="javascript:confirmRequest('?action=remove&id=<?php echo $exampleItem['id']; ?>', 'Are you sure you wish to remove this item?');"><img src="images/aztector_6.gif" alt="Remove" border="0" /></a>
					</td>
				</tr>

				<?php
			}
		} else {
			?>

			<tr>
				<td align="center" colspan="4">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		?>

	</table>
	
	<?php
	$page->Display('footer');
}