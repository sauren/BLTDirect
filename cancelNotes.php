<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure();

if(id_param('oid')){
	$order = new Order(id_param('oid'));
} else {
	redirect('Location: cancel.php');
}

$order->Customer->Get();
$order->Customer->Contact->Get();

$sql = sprintf("select note.*, ot.Type_Name from order_note as note left join order_note_type as ot on note.Order_Note_Type_ID=ot.Order_Note_Type_ID where note.Order_ID=%d and note.Is_Public='Y' order by note.Created_On desc", mysql_real_escape_string($order->ID));

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('oid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
$form->AddField('subject', 'Subject', 'select', '', 'numeric_unsigned', 1, 11, true, 'style="width:350px;"');
$form->AddOption('subject', '', 'Select a Subject');

$data = new DataQuery("select * from order_note_type where Is_Public='Y' order by Type_Name asc");
while($data->Row){
	$form->AddOption('subject', $data->Row['Order_Note_Type_ID'], $data->Row['Type_Name']);
	$data->Next();
}
$data->Disconnect();

$form->AddField('message', 'Note', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:350px; height:100px"');

if(strtolower(param('confirm')) == "true"){
	if($form->Validate()){
		$note = new OrderNote();
		$note->Message = $form->GetValue('message');
		$note->TypeID = $form->GetValue('subject');
		$note->OrderID = $form->GetValue('oid');
		$note->IsPublic = 'Y';
		$note->Add();

		$note->SendToAdmin($order->Customer->Contact->Person->GetFullName(), $order->Customer->GetEmail());

		$order->IsNotesUnread = 'Y';
		$order->Update();

		redirect("Location: cancelNotes.php?sent=true&oid=" . $order->ID);
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Order Notes for Order Ref #<?php echo $order->ID; ?></title>
	<!-- InstanceEndEditable -->

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs_print.css" media="print" />
	<link rel="stylesheet" type="text/css" href="css/Navigation.css" />
	<link rel="stylesheet" type="text/css" href="css/Menu.css" />
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		?>
		<link rel="stylesheet" type="text/css" href="css/Trade.css" />
        <?php
	}
	?>
	<link rel="shortcut icon" href="favicon.ico" />
<!--    <script type='text/javascript' src='http://api.handsetdetection.com/sites/js/43071.js'></script>-->
	<script language="javascript" type="text/javascript" src="js/generic.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance_api.js"></script>
	<script language="javascript" type="text/javascript" src="js/mootools.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance.js"></script>
	<script language="javascript" type="text/javascript" src="js/bltdirect.js"></script>
    <script language="javascript" type='text/javascript' src="js/api.js"></script>
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script language="javascript" type="text/javascript" src="js/bltdirect/template.js"></script>
        <?php
	}
	?>
    
	<script language="javascript" type="text/javascript">
	//<![CDATA[
		<?php
		for($i=0; $i<count($GLOBALS['Cache']['Categories']); $i=$i+2) {
			echo sprintf("menu1.add('navProducts%d', 'navProducts', '%s', '%s', null, 'subMenu');", $i, $GLOBALS['Cache']['Categories'][$i], $GLOBALS['Cache']['Categories'][$i+1]);
		}
		?>
	//]]>
	</script>	
	<!-- InstanceBeginEditable name="head" --><!-- InstanceEndEditable -->
</head>
<body>

    <div id="Wrapper">
        <div id="Header">
            <div id="HeaderInner">
                <?php require('lib/templates/header.php'); ?>
            </div>
        </div>
        <div id="PageWrapper">
            <div id="Page">
                <div id="PageContent">
                    <?php
                    if(strtolower(Setting::GetValue('site_message_active')) == 'true') {
                        ?>

                        <div id="SiteMessage">
                            <div id="SiteMessageLeft">
                                <div id="SiteMessageRight">
                                    <marquee scrollamount="4"><?php echo Setting::GetValue('site_message_value'); ?></marquee>
                                </div>
                            </div>
                        </div>

                        <?php
                    }
                    ?>
                    
                    <a name="top"></a>
                    
                    <!-- InstanceBeginEditable name="pageContent" -->
			<h1>Order Notes for Order Ref #<?php echo $order->ID; ?></h1>
			<p><a href="cancel.php">&laquo; Back to Cancellations</a> | <a href="/cancel.php?orderid=<?php echo $order->ID; ?>">View Order Ref #<?php echo $order->ID; ?> Details</a> </p>

				<?php if(param('sent')) { ?>
				<h3 class="blue">Thank you...</h3>
				<p>Your message has been added to your order history and has been sent to us directly.</p>
				<?php } ?>
				<?php
				// Show Error Report if Form Object validation fails
				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}


				echo '<table class="catProducts" cellspacing="0">';
				$data = new DataQuery($sql);
				if($data->TotalRows > 0){
					while($data->Row){
						if(empty($data->Row['Created_By'])){
							$author = $order->Customer->Contact->Person->GetFullName();
						} else {
							$user = new User($data->Row['Created_By']);
							$author = $user->Person->GetFullName();
						}
						$date = cDatetime($data->Row['Created_On']);
						if(!empty($data->Row['Type_Name'])) echo sprintf('<tr><th colspan="2">%s</th></tr>', $data->Row['Type_Name']);
						echo sprintf('<tr><th style="font-weight:normal;">Date: %s</th><th style="font-weight:normal;">Author: %s</th></tr>', $date, $author);
						echo sprintf('<tr><td colspan="2">%s</td></tr>', $data->Row['Order_Note']);
						$data->Next();
					}
				} else {
					echo '<tr><td class="center">No Order Notes have been entered</td></tr>';
				}
				$data->Disconnect();
				echo '</table><br />';

				// now do the addition form
				echo $form->Open();
				echo $form->GetHTML('action');
				echo $form->GetHTML('confirm');
				echo $form->GetHTML('oid');
				echo '<div id="ShippingCalc"><h3 class="blue">Add Order Note</h3><p>If you would like to add any further information to your order please type below and click the add order note button.</p>';
				echo $form->GetHTML('subject') . "<br />";
				echo $form->GetHTML('message');
				echo sprintf('<br /><input type="submit" name="add order note" value="add order note" class="submit" tabindex="%s" /></div>', $form->GetTabIndex());
				echo $form->Close();
	?>

			<!-- InstanceEndEditable -->
                </div>
            </div>
            <div id="PageFooter">
                <ul class="links">
                    <li><a href="./terms.php" title="BLT Direct Terms and Conditions of Use and Sale">Terms and Conditions</a></li>
                    <li><a href="./privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
                    <li><a href="./company.php" title="About BLT Direct">About Us</a></li>
                    <li><a href="./sitemap.php" title="Map of Site Contents">Site Map</a></li>
                    <li><a href="./support.php" title="Contact BLT Direct">Contact Us</a></li>
                    <li><a href="./index.php" title="Light Bulbs">Light Bulbs</a></li>
                    <li><a href="./products.php?cat=1251&amp;nm=Christmas+Lights" title="Christmas Lights">Christmas Lights</a></li> 
                    <li><a href="./Projector-Lamps.php" title="Projector Lamps">Projector Lamps</a></li>
                    <li><a href="./articles.php" title="Press Releases/Articles">Press Releases/Articles</a></li>
                </ul>
                
                <p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
            </div>
        </div>
        <div id="LeftNav">
            <?php require('lib/templates/left.php'); ?>
        </div>
        <div id="RightNav">
            <?php require('lib/templates/right.php'); ?>
        
            <div id="Azexis">
                <a href="http://www.azexis.com" target="_blank" title="Web Designers">Web Designers</a>
            </div>
        </div>
    </div>
	<script src="<?php print ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT']) ? 'http://www' : 'https://ssl'; ?>.google-analytics.com/urchin.js" type="text/javascript"></script>
	<script type="text/javascript">
	//<![CDATA[
		_uacct = "UA-1618935-2";
		urchinTracker();
	//]]>
	</script>

	<!-- InstanceBeginEditable name="Tracking Script" -->

<!--
<script>
var parm,data,rf,sr,htprot='http'; if(self.location.protocol=='https:')htprot='https';
rf=document.referrer;sr=document.location.search;
if(top.document.location==document.referrer||(document.referrer == '' && top.document.location != '')) {rf=top.document.referrer;sr=top.document.location.search;}
data='cid=256336&rf=' + escape(rf) + '&sr=' + escape(sr); parm=' border="0" hspace="0" vspace="0" width="1" height="1" '; document.write('<img '+parm+' src="'+htprot+'://stats1.saletrack.co.uk/scripts/stinit.asp?'+data+'">');
</script>
<noscript>
<img src="http://stats1.saletrack.co.uk/scripts/stinit.asp?cid=256336&rf=JavaScri
pt%20Disabled%20Browser" border="0" width="0" height="0" />
</noscript>
-->

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>
<?php include('lib/common/appFooter.php'); ?>
