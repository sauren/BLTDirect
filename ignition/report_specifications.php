<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('specification', 'Specification', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('specification', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_group ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('specification', $data->Row['Group_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			report($form->GetValue('parent'), $form->GetValue('specification'));
			exit;
		}
	}

	$page = new Page('Specification Report', 'Please choose a start and end date for your report');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Specification.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Select the specific report variables.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . ' <a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow($form->GetLabel('specification'), $form->GetHTML('specification'));
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($categoryId, $specificationId) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$product = array();
	$categories = getCategories($categoryId);

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_product SELECT p.Product_ID, p.Product_Title FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID %s", (count($categories) > 0) ? sprintf(' AND (pic.Category_ID=%s)', implode(' OR pic.Category_ID=', $categories)) : ''));
	new DataQuery(sprintf("ALTER TABLE temp_product ADD INDEX Product_ID (Product_ID)"));

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID=%d INNER JOIN product_specification AS ps ON p.Product_ID=ps.Product_ID INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID AND psv.Group_ID=%d", mysql_real_escape_string($categoryId), mysql_real_escape_string($specificationId)));
	while($data->Row) {
		new DataQuery(sprintf("DELETE FROM temp_product WHERE Product_ID=%d", $data->Row['Product_ID']));

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT * FROM temp_product ORDER BY Product_ID ASC"));
	while($data->Row) {
		$product[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Specification Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');
	?>

	<br />
	<h3>Missing Specifications</h3>
	<p>Listing all products missing the selected specification for this category.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Name</strong></td>
		</tr>

		<?php
		if(count($product) > 0) {
			foreach($product as $productItem) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><a href="product_profile.php?pid=<?php echo $productItem['Product_ID']; ?>"><?php echo $productItem['Product_ID']; ?></a></td>
					<td><?php echo $productItem['Product_Title']; ?></td>
				</tr>

				<?php
			}
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="2" align="center">No statistics to report on.</td>
			</tr>

			<?php
		}
		?>

	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function getCategories($parentId, $categories = array()) {
	$categories[] = $parentId;

	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", $parentId));
	while($data->Row) {
		$categories = array_merge(getCategories($data->Row['Category_ID']),$categories);

		$data->Next();
	}
	$data->Disconnect();

	return $categories;
}
?>