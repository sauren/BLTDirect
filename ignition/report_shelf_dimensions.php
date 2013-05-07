<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			report($form->GetValue('parent'), ($form->GetValue('subfolders') == 'Y') ? true : false);
			exit;
		}
	}

	$page = new Page('Shelf Dimensions Report', 'Please choose a category for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Shelf Dimensions.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $form->Close();
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($categoryId, $subCategories) {
	$page = new Page('Shelf Dimensions Report', '');
	$page->Display('header');

	$sqlWhere = "WHERE (p.Shelf_Width=0 OR p.Shelf_Height=0 OR p.Shelf_Depth=0) ";

	if($categoryId > 0) {
		if($subCategories) {
			$sqlWhere .= sprintf("AND (c.Category_ID=%d %s) ", mysql_real_escape_string($categoryId), mysql_real_escape_string(getChildCategories($categoryId)));
		} else {
			$sqlWhere .= sprintf("AND c.Category_ID=%d ", mysql_real_escape_string($categoryId));
		}
	} else {
		if(!$subCategories) {
			$sqlWhere .= sprintf("AND (c.Category_ID IS NULL OR c.Category_ID=%d) ", mysql_real_escape_string($categoryId));
		}
	}
	?>

	<br />
	<h3>Missing Dimensions</h3>
	<p>Listing products with incomplete shelf dimensions.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>ID#</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" width="1%">&nbsp;</td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p LEFT JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID %s GROUP BY p.Product_ID ORDER BY p.Product_Title ASC", $sqlWhere));
		if($data->TotalRows > 0) {
			while($data->Row) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $data->Row['Product_ID']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo strip_tags($data->Row['Product_Title']); ?></a></td>
					<td align="right"><a href="product_stock.php?pid=<?php echo $data->Row['Product_ID']; ?>"><img src="images/icon_edit_1.gif" border="0" alt="Edit Stock Settings" /></a></td>
				</tr>

				<?php
				$data->Next();
			}
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td colspan="3" align="center">No products to report on.</td>
			</tr>

			<?php
		}
		$data->Disconnect();
		?>

	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function getChildCategories($parentId) {
	$sql = "";

	$data = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($parentId)));
	while($data->Row){
		$sql .= sprintf("OR c.Category_ID=%d ", mysql_real_escape_string($data->Row['Category_ID']));
		$sql .= getChildCategories($data->Row['Category_ID']);

		$data->Next();
	}
	$data->Disconnect();

	return $sql;
}
?>