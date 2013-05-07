<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

if($action == 'dealt') {
	$session->Secure(3);
	dealt();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function dealt() {
	$product = new Product();
	
	if(isset($_REQUEST['productid']) && $product->Get($_REQUEST['productid'])) {
		$product->DiscontinuedDealt = 'Y';
		$product->Update();
	}
	
	redirectTo('?action=view');
}

function view() {
	$page = new Page('Discontinued Report', '');
	$page->Display('header');
	?>

	<h3>Discontinued Products</h3>
	<p>Lists all discontinued products and who they were discontinued by.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Discontinued On</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Product</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;"><strong>Discontinued By</strong></td>
			<td style="border-bottom: 1px solid #aaaaaa;" width="1%"></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, CONCAT_WS(' ', ps.Name_First, ps.Name_Last) AS Discontinued_Name, p.Discontinued_On FROM product AS p INNER JOIN users AS u ON u.User_ID=p.Discontinued_By INNER JOIN person AS ps ON ps.Person_ID=u.Person_ID WHERE Discontinued='Y' AND Discontinued_Dealt='N' ORDER BY Discontinued_On DESC"));
		if($data->TotalRows > 0) {
			while($data->Row) {
				?>

				<tr>
					<td><?php echo cDatetime($data->Row['Discontinued_On'], 'shortdate'); ?></td>
					<td><?php echo $data->Row['Product_ID']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo strip_tags($data->Row['Product_Title']); ?></a></td>
					<td><?php echo $data->Row['Discontinued_Name']; ?></td>
					<td><a href="?action=dealt&productid=<?php echo $data->Row['Product_ID']; ?>"><img src="images/button-tick.gif" alt="Mark as dealt" /></a></td>
				</tr>

				<?php
				$data->Next();
			}
		} else {
			?>
			
			<tr>
				<td colspan="4" align="center">There are no items available for viewing.</td>
			</tr>
			
			<?php
		}
		$data->Disconnect();
		?>

	</table>

	<?php
	$page->Display('footer');
}