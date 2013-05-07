<?php
require_once('lib/appHeader.php');

if($action == 'openheader'){
	$session->Secure(2);
	openHeader();
	exit;
} elseif($action == 'openbody'){
	$session->Secure(2);
	openBody($session->Warehouse->Contact->ID);
	exit;
} else {
	$session->Secure(2);
	open();
	exit;
}

function open() {
	if(isset($_REQUEST['date'])) {
		?>

		<html>
		<head>
			<title>Orders Despatched Report Print Preview</title>
			<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		</head>

		<frameset border="0" rows="60,*" framespacing="2" frameborder="yes" id="frameSet" bordercolor="#5C6B80">
			<frame src="<?php echo $_SERVER['PHP_SELF']; ?>?action=openheader&date=<?php echo $_REQUEST['date']; ?>" name="frameHeader" frameborder="no" scrolling="auto" border="0"></frame>
			<frame src="<?php echo $_SERVER['PHP_SELF']; ?>?action=openbody&date=<?php echo $_REQUEST['date']; ?>" name="frameBody" frameborder="no" scrolling="auto" border="0"></frame>
		</frameset>

		</html>

		<?php
	} else {
		echo sprintf('<script language="javascript" type="text/javascript">window.self.close();</script>');
	}
}

function openHeader() {
	?>

	<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<link href="css/i_import.css" rel="stylesheet" type="text/css">
		<script language="javascript" type="text/javascript" src="js/generic_1.js"></script>
	</head>
	<body style="padding-bottom: 0px;">

		<table width="100%">
			<tr>
				<td width="50%" align="left"><span class="pageTitle">Orders Despatched Report Print Preview</span></td>
				<td width="50%" align="right"><a href="javascript:popUrl('<?php echo $_SERVER['PHP_SELF']; ?>?action=openbody&print=true&date=<?php echo $_REQUEST['date']; ?>' , 800, 600);"><img src="images/icon_print_1.gif" border="0" width="16" height="16" alt="Print" /></a></td>
			</tr>
		</table>

	</body>
	</html>

	<?php
}

function openBody($supplierId = 0) {
	?>

    <html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<link href="css/i_import.css" rel="stylesheet" type="text/css">
		<script language="javascript" type="text/javascript" src="js/generic_1.js"></script>
	</head>
	<body style="padding-bottom: 0px;">

		<h3>Orders Despatched</h3>
		<br />

	    <table width="100%" border="0">
			<tr>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Order ID</strong></td>
				<td style="border-bottom:1px solid #aaaaaa;"><strong>Date</strong></td>
			</tr>

			<?php
			$data = new DataQuery(sprintf("SELECT * FROM purchase AS p WHERE p.Order_ID>0 AND p.Supplier_ID=%d AND p.Created_On>='%s' AND p.Created_On<ADDDATE('%s', INTERVAL 1 DAY)", mysql_real_escape_string($supplierId), mysql_real_escape_string($_REQUEST['date']), mysql_real_escape_string($_REQUEST['date'])));
			if($data->TotalRows > 0) {
				while($data->Row) {
					?>

	                <tr>
						<td><?php echo $data->Row['Order_ID']; ?></td>
						<td><?php echo $data->Row['Created_On']; ?></td>
					</tr>
	                <tr>
						<td></td>
						<td>

	                        <table width="100%" border="0">
								<tr>
									<td style="border-bottom:1px solid #aaaaaa;" width="15%"><strong>Quantity</strong></td>
									<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
									<td style="border-bottom:1px solid #aaaaaa;" width="15%" align="right"><strong>Cost</strong></td>
									<td style="border-bottom:1px solid #aaaaaa;" width="15%" align="right"><strong>Total</strong></td>
								</tr>

								<?php
		                        $data2 = new DataQuery(sprintf("SELECT pl.*, p.Product_Title FROM purchase_line AS pl LEFT JOIN product AS p ON p.Product_ID=pl.Product_ID WHERE pl.Purchase_ID=%d", $data->Row['Purchase_ID']));
		                        while($data2->Row) {
		                            ?>

	                                <tr>
										<td><?php echo $data2->Row['Quantity']; ?></td>
										<td><?php echo $data2->Row['Product_Title']; ?></td>
										<td align="right">&pound;<?php echo number_format(round($data2->Row['Cost'], 2), 2, '.', ','); ?></td>
										<td align="right">&pound;<?php echo number_format(round($data2->Row['Cost'] * $data2->Row['Quantity'], 2), 2, '.', ','); ?></td>
									</tr>

									<?php
	                                $data2->Next();
								}
								$data2->Disconnect();
								?>

							 </table>
							 <br />

						</td>
					</tr>


					<?php
					$data->Next();
				}
			} else {
				?>

				<tr>
					<td align="center" colspan="2">There are no items available for viewing.</td>
				</tr>

				<?php
			}
			$data->Disconnect();
			?>

	    </table>

    </body>
	</html>

	<?php
	if(isset($_REQUEST['print']) && ($_REQUEST['print'] == 'true')) {
		echo sprintf('<script language="javascript" type="text/javascript">
			window.onload = function() {
				window.print();
				window.close();
			}
			</script>');
	}
}