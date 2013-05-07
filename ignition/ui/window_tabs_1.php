<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Ignition Tabs</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link href="../css/i_nav_tabs.css" rel="stylesheet" type="text/css">
	<script src="../js/generic_1.js"></script>
	<script src="../js/defWin_1.js"></script>
	<script language="javascript" type="text/javascript">
		var navWin = new defWin();
		navWin.addTab('nav_btn_1', '../images/navWin_tab_1.gif', '../images/navWin_tab_2.gif', '../window_warehouse_stats.php');
		navWin.addTab('nav_btn_2', '../images/navWin_tab_1.gif', '../images/navWin_tab_2.gif', '../window_user_recent.php');
		navWin.addTab('nav_btn_3', '../images/navWin_tab_1.gif', '../images/navWin_tab_2.gif', '../window_order_report.php');
		navWin.currentTab = "nav_btn_1";
		navWin.target = window.top.frames['i_window'].frames['i_window_content'];
		navWin.init();
	</script>
</head>
<body>

<table width="100%" height="32" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="3" class="spacer1"><img src="../images/blank.gif" width="3" height="3"></td>
		<td class="navTabOn" id="nav_btn_1" style="width: 80px; height: 32px; background-image: url(../images/navWin_tab_1.gif); background-repeat: no-repeat;"><a href="javascript:navWin.setTab('nav_btn_1');">Warehouse</a></td>
		<td class="navTabOn" id="nav_btn_2" style="width: 80px; height: 32px; background-image: url(../images/navWin_tab_2.gif); background-repeat: no-repeat;"><a href="javascript:navWin.setTab('nav_btn_2');">Recent</a></td>
		<td class="navTabOn" id="nav_btn_3" style="width: 80px; height: 32px; background-image: url(../images/navWin_tab_2.gif); background-repeat: no-repeat;"><a href="javascript:navWin.setTab('nav_btn_3');">Orders</a></td>
		<td>&nbsp;</td>
		<td width="4"><img src="../images/blank.gif" width="4" height="4"></td>
		<td width="30" align="center" valign="middle" class="btn"><a href="javascript:navWin.display();"><img src="../images/hlpWin_btn_1.gif" alt="Maximise/Minimise" name="navWin_resizer" width="18" height="18" hspace="0" vspace="0" border="0" id="hlpWin_resizer"></a></td>
	</tr>
</table>

</body>
</html>