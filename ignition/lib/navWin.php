<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Page.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TreeMenu.php');

if(isset($_REQUEST['action'])){
	if(strtoupper($_REQUEST['action']) == "SETTINGS"){
		/*
			display menu with settings
		*/
		$treeMenu = new TreeMenu("TREE1_NODES", true);
		$treeMenu->SetParams('treemenu', 'Node_ID', 'Parent_ID', 'Caption');
		
		$navWin = new Page("Navigation Settings and Permissions");
		$navWin->AddToBody("<div class=\"window_1\">Navigation Window <br><div class=\"innerWindow\"></div></div>");
		$navWin->Display();
	}
} else {
	/*
		No other requests so just display the menu
	*/
	$treeMenu = new TreeMenu("TREE1_NODES", true);
	$navWin = new Page("Navigation Window");
	$navWin->SetTemplate('navWin_1.tpl');
	$navWin->AddToHead(sprintf("<script>%s</script>", $treeMenu->GetJS(null, 'alpha')));
	$navWin->Display();
}