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
		navWin.layoutType = "cols";
		navWin.frameID = "i_frmSet_2";
		navWin.opened = "220,*";
		navWin.closed = "0,*";
		navWin.resizer = new Array(window.top.frames['i_content'].frames['i_resizer'].document,'navWin_resizer','../images/resizer_btn_1.gif','../images/resizer_btn_2.gif');
		navWin.addTab('nav_btn_1','../images/navWin_tab_1.gif','../images/navWin_tab_2.gif', '../ignition.php?serve=navigation');
		navWin.currentTab = "nav_btn_1";
		navWin.target = window.top.frames['i_nav'].frames['i_nav_content'];
		navWin.init();
	</script>
</head>
<body>

<table width="100%" height="32"  border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td width="3" class="spacer1"><img src="../images/blank.gif" width="3" height="3"></td>
		<td class="navTabOn" id="nav_btn_1" style="width: 80px; height: 32px; background-image: url(../images/navWin_tab_1.gif); background-repeat: no-repeat;"><a href="javascript:navWin.setTab('nav_btn_1');">Navigation</a></td>
		<td>&nbsp;</td>
	</tr>
</table>

</body>
</html>