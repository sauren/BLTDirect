<?php
	require_once('lib/appHeader.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$direct = "index.php";
	if(isset($_REQUEST['direct']) && !empty($_REQUEST['direct'])){
		$direct = $_REQUEST['direct'];
	}

	$login = new Form($_SERVER['PHP_SELF']);
	$login->AddField('action', 'Action', 'hidden', 'login', 'alpha', 4, 6);
	$login->SetValue('action', 'login');
	$login->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$login->AddField('direct', 'Direct', 'hidden', $direct, 'paragraph', 1, 255);
	$login->AddField('username', 'E-mail Address', 'text', '', 'username', 6, 100);
	$login->AddField('password', 'Password', 'password', '', 'password', 6, 100);

	if($action == "login" && isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		$login->Validate();

		if(!$session->Login($login->GetValue('username'), $login->GetValue('password'))){
			$login->AddError("Sorry you were unable to login. Please check your email address and password and try again.");
		}

		if($login->Valid){
			redirect("Location: " . $login->GetValue('direct'));
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/Templates/bltTemplate_Warehouse_Login.dwt.php" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<title>Warehouse Portal</title>
<!-- InstanceEndEditable -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="/warehouse/css/lightbulbs.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="/warehouse/css/lightbulbs_print.css" rel="stylesheet" type="text/css" media="print" />
	<link href="/warehouse/css/default.css" rel="stylesheet" type="text/css" media="screen" />
	<script language="javascript" src="/js/generic.js" type="text/javascript"></script>
    <!-- InstanceBeginEditable name="head" --><!-- InstanceEndEditable -->
</head>
<body>
<div id="Wrapper">
	<div id="Header">
		<a href="/warehouse" title="Back to Home Page"><img src="/images/template/logo_blt_1.jpg" width="185" height="70" border="0" class="logo" alt="BLT Direct Logo" /></a>
		<div id="NavBar" class="warehouse">Warehouse Portal</div>
		<div id="CapTop" class="warehouse">
			<div class="curveLeft"></div>
		</div>
		<ul id="NavTop" class="nav warehouse">
			<li class="contact"><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
			<li class="help"><a href="/support.php" title="Light Bulb, Lamp and Tube Help">Help</a></li>
		</ul>
	</div>

<div id="PageWrapper">
	<div id="Page">
		<div id="PageContent">
		<!-- InstanceBeginEditable name="pageContent" -->
		<h1>Welcome...</h1>
        <p>to the BLT Direct warehouse portal.</p>

        <?php
			if(!$login->Valid){
				echo $login->GetError();
				echo "<br>";
			}

			echo $login->Open();
			echo $login->GetHtml('action');
			echo $login->GetHtml('confirm');
			echo $login->GetHtml('direct');
		?>
		<h3>Login</h3>

		<?php echo $login->GetLabel('username'); ?>:<br />
		<?php echo $login->GetHtml('username'); ?><br />
		<?php echo $login->GetLabel('password'); ?>:<br />
		<?php echo $login->GetHtml('password'); ?><br /><br />

		<input type="submit" class="submit" name="login" value="Login" />

		<?php echo $login->Close(); ?>

		<!-- InstanceEndEditable -->
		</div>
  	</div>

	<div id="PageFooter">
		<ul class="links">
			<li><a href="/privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
			<li><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
		</ul>
		<p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
	</div>
</div>

	<div id="LeftNav">
		<div id="OtherInfo">
			<p class="title">System Navigation will appear here upon login.</p>
		</div>
	</div>
  </div>
</body>
<!-- InstanceEnd --></html>