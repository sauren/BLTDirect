<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Branch.php');
if($action == remove){
	$session->Secure(3);
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['bid'])){
		$branch = new Branch($_REQUEST['bid']);
		$branch->Delete();
		unset($data);
	}
	redirect("Location: branch_view.php");
	exit();
}
else{
	$session->Secure(2);
	view();
	exit();
}

function view(){
	$page = new Page("Branches","Here you can add,view or remove branches belonging to the company");
	$page->Display('header');
	$sql = "SELECT Branch_ID,Branch_Name,Is_HQ FROM branch";
	$table = new DataTable("com");
	$table->SetSQL($sql);
	$table->AddField('ID','Branch_ID','right');
	$table->AddField('Name','Branch_Name');
	$table->AddField('HQ','Is_HQ');
	$table->AddLink('branch_edit.php?bid=%d',"<img src=\"./images/icon_edit_1.gif\" alt=\"Update the branch settings\" border=\"0\">",'Branch_ID');
	$table->AddLink("javascript:confirmRequest('branch_view.php?action=remove&confirm=true&bid=%s','Are you sure you want to remove this branch?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove this branch\" border=\"0\">","Branch_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type = "button" type="submit" value="Add a new branch" class = "btn" onclick="window.location.href=\'./branch_add.php\'">';
	$page->Display('footer');
}
	?>

