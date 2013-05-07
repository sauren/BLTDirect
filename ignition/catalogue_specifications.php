<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "moveup") {
	$session->Secure(3);
	moveup();
	exit;
} elseif($action == "movedown") {
	$session->Secure(3);
	movedown();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function moveup() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/CatalogueSectionCategorySpecification.php');

	$specification = new CatalogueSectionCategorySpecification($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT Catalogue_Section_Category_Specification_ID, Sequence_Number FROM catalogue_section_category_specification WHERE Sequence_Number<%d ORDER BY Sequence_Number DESC LIMIT 0, 1", mysql_real_escape_string($specification->SequenceNumber)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE catalogue_section_category_specification SET Sequence_Number=%d WHERE Catalogue_Section_Category_Specification_ID=%d", $data->Row['Sequence_Number'], mysql_real_escape_string($specification->ID)));
		new DataQuery(sprintf("UPDATE catalogue_section_category_specification SET Sequence_Number=%d WHERE Catalogue_Section_Category_Specification_ID=%d", mysql_real_escape_string($specification->SequenceNumber), $data->Row['Catalogue_Section_Category_Specification_ID']));
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $specification->CatalogueSectionCategoryID));
}

function movedown() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/CatalogueSectionCategorySpecification.php');

	$specification = new CatalogueSectionCategorySpecification($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT Catalogue_Section_Category_Specification_ID, Sequence_Number FROM catalogue_section_category_specification WHERE Sequence_Number>%d ORDER BY Sequence_Number ASC LIMIT 0, 1", mysql_real_escape_string($specification->SequenceNumber)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE catalogue_section_category_specification SET Sequence_Number=%d WHERE Catalogue_Section_Category_Specification_ID=%d", $data->Row['Sequence_Number'], mysql_real_escape_string($specification->ID)));
		new DataQuery(sprintf("UPDATE catalogue_section_category_specification SET Sequence_Number=%d WHERE Catalogue_Section_Category_Specification_ID=%d", mysql_real_escape_string($specification->SequenceNumber), $data->Row['Catalogue_Section_Category_Specification_ID']));
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $specification->CatalogueSectionCategoryID));
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSectionCategorySpecification.php');

	if(isset($_REQUEST['id'])) {
		$specification = new CatalogueSectionCategorySpecification($_REQUEST['id']);
		$specification->Delete();

		redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $specification->CatalogueSectionCategoryID));
	}

	redirect(sprintf("Location: catalogues.php"));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSectionCategory.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSectionCategorySpecification.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSection.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$category = new CatalogueSectionCategory();

	if(!$category->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: catalogues.php"));
	}

	$section = new CatalogueSection($category->CatalogueSectionID);
	$catalogue = new Catalogue($section->CatalogueID);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Category ID', 'hidden', '', 'numeric_unsigned', 1, 11);

	$result = $catalogue->GetSubCategoryProducts($category->CategoryID);
	$productStr = '';

	foreach($result as $productId) {
		$products[$productId] = $productId;
	}

	if(count($products) > 0) {
		$productStr .= sprintf('WHERE Product_ID=%s', implode(' OR Product_ID=', $products));
	}

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_product SELECT Product_ID FROM product %s", mysql_real_escape_string($productStr)));

	$form->AddField('specification', 'Specification', 'select', '', 'anything', 1, 11);
	$form->AddOption('specification', '', '');

	$data = new DataQuery(sprintf("SELECT psg.Group_ID, psg.Name FROM temp_product AS tp INNER JOIN product_specification AS ps ON ps.Product_ID=tp.Product_ID INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps.Value_ID INNER JOIN product_specification_group AS psg ON psg.Group_ID=psv.Group_ID GROUP BY psg.Group_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('specification', $data->Row['Group_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$specification = new CatalogueSectionCategorySpecification();
			$specification->CatalogueSectionCategoryID = $category->ID;
			$specification->SpecificationGroupID = $form->GetValue('specification');
			$specification->Add();

			redirect(sprintf("Location: catalogue_specifications.php?id=%d", $category->ID));
		}
	}

	$page = new Page(sprintf('<a href="catalogue_profile.php?id=%d">Catalogue Profile</a> &gt; <a href="catalogue_sections.php?id=%d">Edit Sections</a> &gt; <a href="catalogue_categories.php?id=%d">Edit Categories</a> &gt; <a href="%s?id=%d">Edit Specifications</a> &gt; Add Specification', $catalogue->ID, $catalogue->ID, $section->ID, $_SERVER['PHP_SELF'], $category->ID), 'Here you can add a specification for this category.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Specification');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('specification'), $form->GetHTML('specification') . $form->GetIcon('specification'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'catalogue_specifications.php?id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $category->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSection.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSectionCategory.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');

	$category = new CatalogueSectionCategory();

	if(!$category->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: catalogues.php"));
	}

	$section = new CatalogueSection($category->CatalogueSectionID);
	$catalogue = new Catalogue($section->CatalogueID);


	$page = new Page(sprintf('<a href="catalogue_profile.php?id=%d">Catalogue Profile</a> &gt; <a href="catalogue_sections.php?id=%d">Edit Sections</a> &gt; <a href="catalogue_categories.php?id=%d">Edit Categories</a> &gt; Edit Specifications', $catalogue->ID, $catalogue->ID, $section->ID), 'Here you can manage specifications for this category.');
	$page->Display('header');

	$table = new DataTable('specifications');
	$table->SetSQL(sprintf("SELECT cscs.Catalogue_Section_Category_Specification_ID, psg.Name AS Specification FROM catalogue_section_category_specification AS cscs LEFT JOIN product_specification_group AS psg ON psg.Group_ID=cscs.Specification_Group_ID WHERE cscs.Catalogue_Section_Category_ID=%d", $category->ID));
	$table->AddField("ID#", "Catalogue_Section_Category_Specification_ID");
	$table->AddField("Specification", "Specification", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("cscs.Sequence_Number");
	$table->AddLink("catalogue_specifications.php?action=moveup&id=%s", "<img src=\"images/aztector_3.gif\" alt=\"Move item up\" border=\"0\">", "Catalogue_Section_Category_Specification_ID");
	$table->AddLink("catalogue_specifications.php?action=movedown&id=%s", "<img src=\"images/aztector_4.gif\" alt=\"Move item down\" border=\"0\">", "Catalogue_Section_Category_Specification_ID");
	$table->AddLink("javascript:confirmRequest('catalogue_specifications.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Catalogue_Section_Category_Specification_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add specification" class="btn" onclick="window.location.href=\'catalogue_specifications.php?action=add&id=%d\'" />', $category->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>