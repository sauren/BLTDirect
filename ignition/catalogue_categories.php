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
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/CatalogueSectionCategory.php');

	$category = new CatalogueSectionCategory($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT Catalogue_Section_Category_ID, Sequence_Number FROM catalogue_section_category WHERE Sequence_Number<%d ORDER BY Sequence_Number DESC LIMIT 0, 1", mysql_real_escape_string($category->SequenceNumber)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE catalogue_section_category SET Sequence_Number=%d WHERE Catalogue_Section_Category_ID=%d", $data->Row['Sequence_Number'], mysql_real_escape_string($category->ID)));
		new DataQuery(sprintf("UPDATE catalogue_section_category SET Sequence_Number=%d WHERE Catalogue_Section_Category_ID=%d", mysql_real_escape_string($category->SequenceNumber), $data->Row['Catalogue_Section_Category_ID']));
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $category->CatalogueSectionID));
}

function movedown() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/CatalogueSectionCategory.php');

	$category = new CatalogueSectionCategory($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT Catalogue_Section_Category_ID, Sequence_Number FROM catalogue_section_category WHERE Sequence_Number>%d ORDER BY Sequence_Number ASC LIMIT 0, 1", mysql_real_escape_string($category->SequenceNumber)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE catalogue_section_category SET Sequence_Number=%d WHERE Catalogue_Section_Category_ID=%d", $data->Row['Sequence_Number'], mysql_real_escape_string($category->ID)));
		new DataQuery(sprintf("UPDATE catalogue_section_category SET Sequence_Number=%d WHERE Catalogue_Section_Category_ID=%d", mysql_real_escape_string($category->SequenceNumber), $data->Row['Catalogue_Section_Category_ID']));
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $category->CatalogueSectionID));
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSectionCategory.php');

	if(isset($_REQUEST['id'])) {
		$category = new CatalogueSectionCategory($_REQUEST['id']);
		$category->Delete();

		redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $category->CatalogueSectionID));
	}

	redirect(sprintf("Location: catalogues.php"));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSectionCategory.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSection.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$section = new CatalogueSection();

	if(!$section->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: catalogues.php"));
	}

	$catalogue = new Catalogue($section->CatalogueID);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Section ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11, false);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 120, true, 'style="width: 300px;"');
	$form->AddField('description', 'Description', 'textarea', '', 'anything', 1, 1024, false, 'style="width: 100%;"');
	$form->AddField('image', 'Image', 'select', $category->CategoryCatalogueImageID, 'numeric_unsigned', 1, 11, false);
	$form->AddOption('image', '0', '');

	$data = new DataQuery(sprintf("SELECT Category_Catalogue_Image_ID, Title FROM category_catalogue_image WHERE Category_ID=%d ORDER BY Title ASC", mysql_real_escape_string($category->CategoryID)));
	while($data->Row) {
		$form->AddOption('image', $data->Row['Category_Catalogue_Image_ID'], $data->Row['Title']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('sortmethod', 'Sort Method', 'select', 'Code', 'anything', 1, 30, true);
	$form->AddOption('sortmethod', 'Code', 'Code');
	$form->AddOption('sortmethod', 'Quickfind', 'Quickfind');
	$form->AddOption('sortmethod', 'Specification', 'Specification');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			if($form->GetValue('parent') == 0) {
				$form->AddError('The selected Parent is invalid.', 'parent');
			}

			if($form->Valid) {
				$category = new CatalogueSectionCategory();
				$category->CatalogueSectionID = $section->ID;
				$category->CategoryID = $form->GetValue('parent');
				$category->Title = $form->GetValue('title');
				$category->Description = $form->GetValue('description');
				$category->CategoryCatalogueImageID = $form->GetValue('image');
				$category->SortMethod = $form->GetValue('sortmethod');
				$category->Add();

				redirect(sprintf("Location: catalogue_categories.php?id=%d", $section->ID));
			}
		}
	}

	$page = new Page(sprintf('<a href="catalogue_profile.php?id=%d">Catalogue Profile</a> &gt; <a href="catalogue_sections.php?id=%d">Edit Sections</a> &gt; <a href="%s?id=%d">Edit Categories</a> &gt; Add Category', mysql_real_escape_string($catalogue->ID), mysql_real_escape_string($catalogue->ID), mysql_real_escape_string($_SERVER['PHP_SELF']), mysql_real_escape_string($section->ID)), 'Here you can add a category for this section.');
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$parentCategory = new Category();

	if($form->GetValue('parent') > 0) {
		$parentCategory->Get($form->GetValue('parent'));
	}

	$window = new StandardWindow('Add Category');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . ' <a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', sprintf('<span id="parentCaption">%s</span>', ($parentCategory->ID > 0) ? $parentCategory->Name : '<em>None</em>') . $form->GetHTML('parent') . $form->GetIcon('parent'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow($form->GetLabel('sortmethod'), $form->GetHTML('sortmethod') . $form->GetIcon('sortmethod'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'catalogue_categories.php?id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $section->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryCatalogueImage.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSection.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSectionCategory.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSectionCategoryExclusion.php');
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
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Category ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', $category->CategoryID, 'numeric_unsigned', 1, 11, false);
	$form->AddField('title', 'Title', 'text', $category->Title, 'anything', 1, 120, true, 'style="width: 300px;"');
	$form->AddField('description', 'Description', 'textarea', $category->Description, 'anything', 1, 1024, false, 'style="width: 100%;"');
	$form->AddField('image', 'Image', 'select', $category->CategoryCatalogueImageID, 'numeric_unsigned', 1, 11, false);
	$form->AddOption('image', '0', '');

	$data = new DataQuery(sprintf("SELECT Category_Catalogue_Image_ID, Title FROM category_catalogue_image WHERE Category_ID=%d ORDER BY Title ASC", mysql_real_escape_string($category->CategoryID)));
	while($data->Row) {
		$form->AddOption('image', $data->Row['Category_Catalogue_Image_ID'], $data->Row['Title']);

		$data->Next();
	}
	$data->Disconnect();

	$exclusions = array();

	$data = new DataQuery(sprintf("SELECT Catalogue_Section_Category_Exclusion_ID, Category_ID FROM catalogue_section_category_exclusion WHERE Catalogue_Section_Category_ID=%d", mysql_real_escape_string($category->ID)));
	while($data->Row) {
		$exclusions[$data->Row['Category_ID']] = $data->Row['Catalogue_Section_Category_Exclusion_ID'];

		$data->Next();
	}
	$data->Disconnect();

	$subCategories = getCategories($category->CategoryID);

	for($i=0; $i<count($subCategories); $i++) {
		$form->AddField('category_'.$i, 'Exclude', 'checkbox', isset($exclusions[$subCategories[$i]['Object']->ID]) ? 'Y' : 'N', 'boolean', 1, 1, false);
	}

	$form->AddField('sortmethod', 'Sort Method', 'select', $category->SortMethod, 'anything', 1, 30, true, 'onchange="toggleSortSpecifications(this);"');
	$form->AddOption('sortmethod', 'Code', 'Code');
	$form->AddOption('sortmethod', 'Quickfind', 'Quickfind');
	$form->AddOption('sortmethod', 'Specification', 'Specification');
	$form->AddField('sortspec', 'Sort Specification Group', 'select', $category->SortSpecificationGroupID, 'numeric_unsigned', 1, 11, true, ($form->GetValue('sortmethod') == 'Specification') ? '' : 'disabled="disabled"');
	$form->AddOption('sortspec', '0', '');

	$data = new DataQuery(sprintf("SELECT psg.Group_ID, psg.Name FROM catalogue_section_category_specification AS cscs INNER JOIN product_specification_group AS psg ON psg.Group_ID=cscs.Specification_Group_ID WHERE cscs.Catalogue_Section_Category_ID=%d ORDER BY psg.Name ASC", mysql_real_escape_string($category->ID)));
	while($data->Row) {
		$form->AddOption('sortspec', $data->Row['Group_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			if($form->GetValue('parent') == 0) {
				$form->AddError('The selected Parent is invalid.', 'parent');
			}

			if($form->Valid) {
				$category->CategoryID = $form->GetValue('parent');
				$category->Title = $form->GetValue('title');
				$category->Description = $form->GetValue('description');
				$category->CategoryCatalogueImageID = $form->GetValue('image');
				$category->SortMethod = $form->GetValue('sortmethod');
				$category->SortSpecificationGroupID = ($category->SortMethod == 'Specification') ? $form->GetValue('sortspec') : 0;
				$category->Update();

				for($i=0; $i<count($subCategories); $i++) {
					if($form->GetValue('category_'.$i) == 'Y') {
						if(!isset($exclusions[$subCategories[$i]['Object']->ID])) {
							$exclusion = new CatalogueSectionCategoryExclusion();
							$exclusion->CatalogueSectionCategoryID = $category->ID;
							$exclusion->CategoryID = $subCategories[$i]['Object']->ID;
							$exclusion->Add();
						}
					} else {
						if(isset($exclusions[$subCategories[$i]['Object']->ID])) {
							$exclusion = new CatalogueSectionCategoryExclusion();
							$exclusion->Delete($exclusions[$subCategories[$i]['Object']->ID]);
						}
					}
				}

				redirect(sprintf("Location: catalogue_categories.php?id=%d", $category->CatalogueSectionID));
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleSortSpecifications = function(obj) {
			var e = document.getElementById(\'sortspec\');

			if(e) {
				if(obj.value == \'Specification\') {
					e.removeAttribute(\'disabled\');
				} else {
					e.setAttribute(\'disabled\', \'disabled\');
				}
			}
		}
		</script>');

	$page = new Page(sprintf('<a href="catalogue_profile.php?id=%d">Catalogue Profile</a> &gt; <a href="catalogue_sections.php?id=%d">Edit Sections</a> &gt; <a href="%s?id=%d">Edit Categories</a> &gt; Update Category', $section->CatalogueID, $section->CatalogueID, $_SERVER['PHP_SELF'], $category->CatalogueSectionID), 'Here you can update a category for this section.');
	$page->SetEditor(true);
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$parentCategory = new Category();

	if($form->GetValue('parent') > 0) {
		$parentCategory->Get($form->GetValue('parent'));
	}

	$window = new StandardWindow('Update Category');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . ' <a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', sprintf('<span id="parentCaption">%s</span>', ($parentCategory->ID > 0) ? $parentCategory->Name : '<em>None</em>') . $form->GetHTML('parent') . $form->GetIcon('parent'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));

	if($category->CategoryCatalogueImageID > 0) {
		$image = new CategoryCatalogueImage();

		if($image->Get($category->CategoryCatalogueImageID)) {
			echo $webForm->AddRow('Current Image', sprintf('<img src="%s%s" alt="%s" />', $GLOBALS['CATEGORY_CATALOGUE_IMAGE_DIR_WS'], $image->Thumb->FileName, $image->Title));
		}
	}

	echo $webForm->AddRow($form->GetLabel('sortmethod'), $form->GetHTML('sortmethod') . $form->GetIcon('sortmethod'));
	echo $webForm->AddRow($form->GetLabel('sortspec'), $form->GetHTML('sortspec') . $form->GetIcon('sortspec'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'catalogue_categories.php?id=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $category->CatalogueSectionID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();

	if(count($subCategories) > 0) {
		echo $window->AddHeader('Exclude sub categories from catalogue');
		echo $window->OpenContent();
		echo $webForm->Open();

		for($i=0; $i<count($subCategories); $i++) {
			echo $webForm->AddRow($form->GetLabel('category_'.$i), $form->GetHTML('category_'.$i) . ' ' . $subCategories[$i]['Path']);
		}

		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'catalogue_categories.php?id=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $category->CatalogueSectionID, $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
	}

	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function getCategories($parentId = 0, $path = '') {
	$category = array();

	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($parentId)));
	while($data->Row) {
		$subCategory = new Category($data->Row['Category_ID']);

		$subPath = trim(sprintf('%s / %s', $path, $subCategory->Name));
		$subPath = (substr($subPath, 0, 1) == '/') ? substr($subPath, 1) : $subPath;

		$category[] = array('Object' => $subCategory, 'Path' => $subPath);

		$category = array_merge($category, getCategories($data->Row['Category_ID'], $subPath));

		$data->Next();
	}
	$data->Disconnect();

	return $category;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSection.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');

	$section = new CatalogueSection();

	if(!$section->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: catalogues.php"));
	}

	$catalogue = new Catalogue($section->CatalogueID);

	$page = new Page(sprintf('<a href="catalogue_profile.php?id=%d">Catalogue Profile</a> &gt; <a href="catalogue_sections.php?id=%d">Edit Sections</a> &gt; Edit Categories', $catalogue->ID, $catalogue->ID), 'Here you can manage categories for this section.');
	$page->Display('header');

	$table = new DataTable('categories');
	$table->SetSQL(sprintf("SELECT * FROM catalogue_section_category AS csc WHERE csc.Catalogue_Section_ID=%d", mysql_real_escape_string($section->ID)));
	$table->AddField("ID#", "Catalogue_Section_Category_ID");
	$table->AddField("Title", "Title", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Sequence_Number");
	$table->AddLink("catalogue_specifications.php?id=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Section Category Specifications\" border=\"0\">", "Catalogue_Section_Category_ID");
	$table->AddLink("catalogue_categories.php?action=moveup&id=%s", "<img src=\"images/aztector_3.gif\" alt=\"Move item up\" border=\"0\">", "Catalogue_Section_Category_ID");
	$table->AddLink("catalogue_categories.php?action=movedown&id=%s", "<img src=\"images/aztector_4.gif\" alt=\"Move item down\" border=\"0\">", "Catalogue_Section_Category_ID");
	$table->AddLink("catalogue_categories.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Catalogue_Section_Category_ID");
	$table->AddLink("javascript:confirmRequest('catalogue_categories.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Catalogue_Section_Category_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add category" class="btn" onclick="window.location.href=\'catalogue_categories.php?action=add&id=%d\'" />', $section->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}