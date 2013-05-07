<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierCategoryCharge.php');

$suppliers = array();

$data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS('<br />', CONCAT('<strong>', TRIM(o.Org_Name), '</strong>'), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, CONCAT('<strong>', TRIM(o.Org_Name), '</strong>'), CONCAT('<strong>', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), '</strong><br />&nbsp;'))) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE s.Is_Favourite='Y' ORDER BY Supplier_Name ASC"));
while($data->Row) {
	$suppliers[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$activeCategories = array(14, 15, 16, 235, 241, 265, 284);
$categories = array();

$data = new DataQuery(sprintf("SELECT pc.Category_ID, pc.Category_Title FROM product_categories AS pc WHERE pc.Category_Parent_ID=1 AND (pc.Category_ID=%s) ORDER BY pc.Category_Title ASC", implode(' OR pc.Category_ID=', $activeCategories)));
while($data->Row) {
	$categories[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$charges = array();

$data = new DataQuery(sprintf("SELECT * FROM supplier_category_charge"));
while($data->Row) {
	if(!isset($charges[$data->Row['SupplierID']])) {
		$charges[$data->Row['SupplierID']] = array();
	}

	if(!isset($charges[$data->Row['SupplierID']][$data->Row['CategoryID']])) {
		$charges[$data->Row['SupplierID']][$data->Row['CategoryID']] = array();
	}

	$charges[$data->Row['SupplierID']][$data->Row['CategoryID']] = $data->Row['Charge'];

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

foreach($suppliers as $supplier) {
	foreach($categories as $category) {
		$form->AddField(sprintf('charge_%s_%s', $supplier['Supplier_ID'], $category['Category_ID']), sprintf('Charge of \'%s\' for \'%s\'', $category['Catgory_Title'], $supplier['Supplier_Name']), 'text', isset($charges[$supplier['Supplier_ID']][$category['Category_ID']]) ? $charges[$supplier['Supplier_ID']][$category['Category_ID']] : 0, 'float', 1, 11, false, 'size="3"');
	}
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		foreach($suppliers as $supplier) {
			foreach($categories as $category) {
				$charge = new SupplierCategoryCharge();

				$chargeCost = $form->GetValue(sprintf('charge_%s_%s', $supplier['Supplier_ID'], $category['Category_ID']));

				if($charge->GetByReference($supplier['Supplier_ID'], $category['Category_ID'])) {
					if($chargeCost > 0) {
						$charge->Charge = $chargeCost;
						$charge->Update();
					} else {
						$charge->Delete();
					}
				} else {
					if($chargeCost > 0) {
						$charge->Supplier->ID = $supplier['Supplier_ID'];
						$charge->Category->ID = $category['Category_ID'];
						$charge->Charge = $chargeCost;
						$charge->Add();
					}
				}
			}
		}

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
}

$page = new Page('Supplier Category Charges', 'Specify additional cost charges for products within each top level category for suppliers.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
	<thead>
		<tr>
			<th nowrap="nowrap">&nbsp;</th>
			<th nowrap="nowrap" colspan="<?php echo count($categories); ?>">Categories</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td nowrap="nowrap">&nbsp;</td>

			<?php
			foreach($categories as $category) {
				echo sprintf('<td nowrap="nowrap">%s</td>', $category['Category_Title']);
			}
			?>

		</tr>

		<?php
		foreach($suppliers as $supplier) {
			?>

			<tr>
				<td><?php echo $supplier['Supplier_Name']; ?></td>

				<?php
				foreach($categories as $category) {
					echo sprintf('<td nowrap="nowrap">&pound;%s</td>', $form->GetHTML(sprintf('charge_%s_%s', $supplier['Supplier_ID'], $category['Category_ID'])));
				}
				?>

			</tr>

			<?php
		}
		?>
	</tbody>
</table>
<br />

<input type="submit" name="update" value="update" class="btn" />

<?php
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');