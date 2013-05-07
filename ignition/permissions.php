<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/RegistryPermissions.php');

if($action == 'add'){
	$session->Secure(3);
	addPermissions();
	exit;
} elseif($action == 'copy'){
    $session->Secure(3);
    copyPermissions();
    exit;
} elseif($action == 'remove'){
	$session->Secure(3);
	if(isset($_REQUEST['level'])){
		removePermissions();
	} else {
		redirect("Location: access_levels.php");
	}
} else {
	if(isset($_REQUEST['level'])){
		viewPermissions();
	} else {
		redirect("Location: access_levels.php");
	}
	exit;
}

function removePermissions(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == 'true'){
		if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
			$permissions = new RegistryPermissions();
			$permissions->delete($_REQUEST['id']);
		}
	}
	redirect(sprintf("Location: ?action=view%s", extractVars("action,id,confirm")));
}

function addPermissions(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	/* Get Access Level Name */
	$accessLevelID = $_REQUEST['level'];
	$accessLevel = new DataQuery(sprintf("select Access_Level from access_levels where Access_ID=%d", mysql_real_escape_string($accessLevelID)));
	$accessLevelName = $accessLevel->Row["Access_Level"];
	$accessLevel->Disconnect();
	$accessLevel = NULL;

	/* Initiate the Form */
	$form = new Form("");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('level', 'Access Level', 'hidden', $accessLevelID, 'numeric_unsigned', 1, 11);
	$form->AddField('script', 'Script Name', 'select', '', 'numeric_unsigned', 1, 11);

	$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_permission SELECT Registry_ID FROM registry_permissions WHERE Access_ID=%d", mysql_real_escape_string($accessLevelID)));
	$data->Disconnect();

	$script = new DataQuery(sprintf("select r.* from registry AS r LEFT JOIN temp_permission AS rp ON rp.Registry_ID=r.Registry_ID WHERE rp.Registry_ID IS NULL ORDER BY Script_Name"));
	do{
		$form->AddOption('script', $script->Row['Registry_ID'], $script->Row['Script_Name']);
		$script->Next();
	} while($script->Row);
	$script->Disconnect();

	$form->AddField('permission', 'Permissions', 'select', '', 'numeric_unsigned', 1, 11);
	$permissions = new DataQuery("select * from permissions ORDER BY Permission_ID DESC");
	do{
		$form->AddOption('permission', $permissions->Row['Permission_ID'], $permissions->Row['Permission']);
		$permissions->Next();
	} while($permissions->Row);
	$permissions->Disconnect();

	// Check if the form has been submitted
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$insertForm = new RegistryPermissions();
			$insertForm->access = $form->GetValue('level');
			$insertForm->permission = $form->GetValue('permission');
			$insertForm->registry = $form->GetValue('script');
			$insertForm->add();

			redirect('Location: ?level=' . $form->GetValue('level'));
		}
	}

	/* Start Page */
	$page = new Page(sprintf("Adding Permissions for %s", $accessLevelName),
	"You are about to add a new permission to an Access Level. This will grant all users of this access level with the permissions applied here.");
	$page->Display('header');
	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow(sprintf('Add Script Permissions to ', $accessLevelName));

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('level');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow("Access Level", $accessLevelName);
	echo $webForm->AddRow($form->GetLabel('script'), $form->GetHTML('script') . $form->GetIcon('script'));
	echo $webForm->AddRow($form->GetLabel('permission'), $form->GetHTML('permission') . $form->GetIcon('permission'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back to permissions" value="back to permissions" class="btn" onclick="window.location.href=\'?action=view%s\'"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', extractVars('action'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function viewPermissions(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	/* Get Access Level Name */
	$accessLevelID = $_REQUEST['level'];
	$accessLevel = new DataQuery(sprintf("select Access_Level from access_levels where Access_ID=%d", mysql_real_escape_string($accessLevelID)));
	$accessLevelName = $accessLevel->Row["Access_Level"];
	$accessLevel->Disconnect();
	$accessLevel = NULL;

	/* Start Page */
	$page = new Page(sprintf("Script Permissions for %s", $accessLevelName),
	"Ignition's secure sessions assume denial of access to all scripts. Therefore you need to grant access permissions to each individual Access Level.");


	$page->Display('header');

	echo '<input type="button" name="back to access levels" value="back to access levels" class="btn" onclick="window.location.href=\'access_levels.php\'"> ';

	echo sprintf('<input type="button" name="add to permissions" value="add to permissions" class="btn" onclick="window.location.href=\'permissions.php?action=add%s\'">', extractVars('action'));
    
    echo sprintf('&nbsp;<input type="button" name="copy permissions" value="copy permissions" class="btn" onclick="window.location.href=\'permissions.php?action=copy%s\'"><br /><br />', extractVars('action'));

    $table = new DataTable('permissions');
	$table->SetSQL(sprintf("SELECT registry_permissions.Registry_Permission_ID, permissions.Permission, registry.Script_Name
						FROM registry_permissions
						INNER JOIN permissions ON registry_permissions.Permission_ID = permissions.Permission_ID
						INNER JOIN registry ON registry_permissions.Registry_ID = registry.Registry_ID
						WHERE registry_permissions.Access_ID = %d", mysql_real_escape_string($accessLevelID)));

	$table->AddField('ID#', 'Registry_Permission_ID', 'right');
	$table->AddField('Registry Script', 'Script_Name', 'left');
	$table->AddField('Permission', 'Permission', 'left');
	$table->AddLink("javascript:confirmRequest('permissions.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this permission from this access level? Removing this permissionmay affect existing users.');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Registry_Permission_ID");
	$table->SetMaxRows(100);
	$table->SetOrderBy("Script_Name");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="back to access levels" value="back to access levels" class="btn" onclick="window.location.href=\'access_levels.php\'"> ';
	echo sprintf('<input type="button" name="add to permissions" value="add to permissions" class="btn" onclick="window.location.href=\'permissions.php?action=add%s\'">', extractVars('action'));
    echo sprintf('&nbsp;<input type="button" name="copy permissions" value="copy permissions" class="btn" onclick="window.location.href=\'permissions.php?action=copy%s\'">', extractVars('action'));

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function copyPermissions(){
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

    /* Get Access Level Name */
    $accessLevelID = $_REQUEST['level'];
    $accessLevel = new DataQuery(sprintf("select Access_Level from access_levels where Access_ID=%d", mysql_real_escape_string($accessLevelID)));
    $accessLevelName = $accessLevel->Row["Access_Level"];
    $accessLevel->Disconnect();
    $accessLevel = NULL;
    /* Initiate the Form */
    $form = new Form("permissions.php");
    $form->AddField('action', 'Action', 'hidden', 'copy', 'alpha', 4, 4);
    $form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
    $form->AddField('level', 'Access Level One', 'hidden', $accessLevelID, 'numeric_unsigned', 1, 11);


    $form->AddField('levelTwo', 'Access Level Two', 'select', '', 'numeric_unsigned', 1, 11, true);
    $levels = new DataQuery("select * from access_levels where Access_ID != {$accessLevelID}");
    do{
        $form->AddOption('levelTwo', $levels->Row['Access_ID'], $levels->Row['Access_Level']);
        $levels->Next();
    } while($levels->Row);
    $levels->Disconnect();
    
    // Check if the form has been submitted
    if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
        if($form->Validate()){
            
            $sql = 'delete from registry_permissions where Access_ID='.mysql_real_escape_string($form->GetValue('levelTwo'));
            
            $deletion = new DataQuery($sql);
            
            
            $data = new DataQuery('select * from registry_permissions where Access_ID='.mysql_real_escape_string($form->GetValue('level')));
            while($data->Row){
            	
            	$insertForm = new RegistryPermissions();
				$insertForm->access = $form->GetValue('levelTwo');
				$insertForm->permission = $data->Row['Permission_ID'];
				$insertForm->registry =  $data->Row['Registry_ID'];
				$insertForm->add();              
                $data->Next();
            }
            
            redirect(sprintf("Location: ?level=%d", $accessLevelID));
        }
    }
    
    $page = new Page(sprintf("Copying Permissions of %s", $accessLevelName),
                            "You are about to copy all permissions of {$accessLevelName} to another Access Level. This will grant all users of the new access level with the permissions applied here.");
    
    $page->Display('header');

    if(!$form->Valid){
        echo $form->GetError();
        echo "<br>";
    }

    $window = new StandardWindow('Copy Permissions');

    echo $form->Open();
    echo $form->GetHTML('action');
    echo $form->GetHTML('confirm');
    echo $form->GetHTML('level');
    echo $window->Open();
    echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
    echo $window->OpenContent();
    $webForm = new StandardForm;
    echo $webForm->Open();
    echo $webForm->AddRow($form->GetLabel('level'),$accessLevelName);
    echo $webForm->AddRow($form->GetLabel('levelTwo'), $form->GetHTML('levelTwo') . $form->GetIcon('levelTwo'));
    echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back to permissions" value="back to permissions" class="btn" onclick="window.location.href=\'?action=view%s\'"> <input type="submit" name="copy" value="copy" class="btn" tabindex="%s">', extractVars('action'), $form->GetTabIndex()));
    echo $webForm->Close();
    echo $window->CloseContent();
    echo $window->Close();
    echo $form->Close();
    $page->Display('footer');
    require_once('lib/common/app_footer.php');
}