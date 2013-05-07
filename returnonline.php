<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnAuto.php');

$session->Secure();

$months = 24;

if(!id_param('despatchid')) {
	redirectTo('despatches.php');
}

$contacts = array();

if($session->Customer->Contact->HasParent) {
	$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID)));
	while($data->Row) {
		$contacts[] = $data->Row['Contact_ID'];
		
		$data->Next();	
	}
	$data->Disconnect();
} else {
	$contacts[] = $session->Customer->Contact->ID;
}

$data = new DataQuery(sprintf("SELECT d.Despatched_On, p.Postage_Days FROM despatch AS d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID INNER JOIN postage AS p ON p.Postage_ID=o.Postage_ID INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE c.Contact_ID IN (%s) AND d.Despatch_ID=%d", implode(', ', $contacts), mysql_real_escape_string(id_param('despatchid'))));

if($data->TotalRows > 0) {	
	$thresholdMin = strtotime($data->Row['Despatched_On']) + ($data->Row['Postage_Days'] * 2 * 86400);
	$thresholdMax = mktime(date('H'), date('i'), date('s'), date('m')-$months, date('d'), date('Y'));

	if(($thresholdMin > time()) || ($thresholdMax > strtotime($data->Row['Despatched_On']))) {
		redirectTo('despatches.php');
	}
}
$data->Disconnect();

$despatch = new Despatch(id_param('despatchid'));
$despatch->GetLines();

$reasons = array();

$data = new DataQuery(sprintf('SELECT Reason_Title FROM return_reason ORDER BY Reason_Title ASC'));
while($data->Row) {
	$reasons[] = $data->Row['Reason_Title'];

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('despatchid', 'Despatch ID', 'hidden', '', 'numeric_unsigned', 1, 11);

foreach($despatch->Line as $line) {
	$form->AddField('quantity_'.$line->ID, 'Quantity', 'select', 0, 'numeric_unsigned', 1, 11);

	for($i=0; $i<=$line->Quantity; $i++) {
		$form->AddOption('quantity_'.$line->ID, $i, $i);		
	}

	$form->AddField('reason_'.$line->ID, 'Reason', 'select', '', 'anything', 1, 240, false);
	$form->AddOption('reason_'.$line->ID, '', '');

	foreach($reasons as $reason) {
		$form->AddOption('reason_'.$line->ID, $reason, $reason);
	}
}

if(param('confirm')) {
	foreach($despatch->Line as $line) {
		if($form->GetValue('quantity_'.$line->ID) > 0) {
			$form->InputFields['reason_'.$line->ID]->Required = true;
		}
	}

	if($form->Validate()) {
		$despatchLine = null;

		$isAuto = true;

		if($isAuto) {
			if(strtotime('+3 month', strtotime($despatch->CreatedOn)) < time()) {
				$isAuto = false;
			}
		}

		if($isAuto) {
			$despatch->Person->Address->Country->Get();

			if($despatch->Person->Address->Country->ISOCode2 != 'GB') {
				$isAuto = false;
			}
		}

		if($isAuto) {
			$quantity = 0;

			foreach($despatch->Line as $line) {
				if($form->GetValue('quantity_'.$line->ID) > 0) {
					$quantity += $form->GetValue('quantity_'.$line->ID);
					
					$despatchLine = $line;
				}
			}

			if($quantity <> 1) {
				$isAuto = false;
			}
		}

		if($isAuto) {
			if($form->GetValue('reason_'.$despatchLine->ID) != 'Not Received') {
				$isAuto = false;
			}
		}

		if($isAuto) {
			if(Setting::GetValue('auto_return_value') < $despatchLine->Product->GetBestCost()) {
				$isAuto = false;
			}
		}

		if($isAuto) {
			$despatchLine->Product->Get();

			$data = new DataQuery(sprintf("SELECT Shipping_Class_ID FROM shipping_class WHERE Is_Default='Y'"));
			if($data->Row['Shipping_Class_ID'] != mysql_real_escape_string($despatchLine->Product->ShippingClass->ID)) {
				$isAuto = false;
			}
			$data->Disconnect();
		}

		if($isAuto) {
			$return = new ReturnAuto();
			$return->order->ID = $despatch->Order->ID;
			$return->product->ID = $despatchLine->Product->ID;
			$return->add();

			redirectTo(sprintf('?despatchid=%d&action=returned', $despatch->ID));
		} else {
			$despatch->Order->Get();

			foreach($despatch->Line as $line) {
				if($form->GetValue('quantity_'.$line->ID) > 0) {
					$data = new DataQuery(sprintf('SELECT Reason_ID FROM return_reason WHERE Reason_Title LIKE \'%s\'', $form->GetValue('reason_'.$line->ID)));
					$reasonId = $data->Row['Reason_ID'];
					$data->Disconnect();

					$data = new DataQuery(sprintf('SELECT ol.Order_Line_ID, ol.Invoice_ID FROM despatch AS d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Product_ID=%d WHERE d.Despatch_ID=%d LIMIT 0, 1', mysql_real_escape_string($line->Product->ID,$despatch->ID)));
					$orderLineId = $data->Row['Order_Line_ID'];
					$invoiceId =$data->Row['Invoice_ID'];
					$data->Disconnect();

		            $return  = new ProductReturn();
		            $return->OrderLine->ID = $orderLineId;
		            $return->Invoice->ID = $invoiceId;
		            $return->Customer->ID = $despatch->Order->Customer->ID;
		            $return->Reason->ID = $reasonId;
		            $return->Quantity = $form->GetValue('quantity_'.$line->ID);
		            $return->RequestedOn = now();
		            $return->Add();
		        }
		    }
		}

		redirectTo(sprintf('?despatchid=%d&action=returned', $despatch->ID));
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Return Online</title>
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
					<h1>Return Online</h1>
					<div id="orderConfirmation">
						<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returns.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
					</div>
					<p>Select the lines you would like to raise a return against.</p>

					<?php
					if(!$form->Valid) {
						echo $form->GetError();
						echo '<br />';
					}

					if($action == 'returned') {
						$bubble = new Bubble('Return Raised', 'Your online return has been raised. Please be patient while we process your request. We aim to be back to you within 3 working hours.');

						echo $bubble->GetHTML();
						echo '<br />';			
					}

					echo $form->Open();
					echo $form->GetHTML('confirm');
					echo $form->GetHTML('despatchid');
					?>

					<table cellspacing="0" class="myAccountOrderHistory">
						<tr>
							<th>Quantity</th>
							<th>Product</th>
							<th>Quickfind</th>
							<th>Reason</th>
						</tr>

						<?php
						foreach($despatch->Line as $line) {
							?>

							<tr>
								<td><?php echo $form->GetHTML('quantity_'.$line->ID); ?></td>
								<td><?php echo $line->Product->Name; ?></td>
								<td><a href="product.php?pid=<?php echo $line->Product->ID; ?>"><?php echo $line->Product->ID; ?></a></td>
								<td><?php echo $form->GetHTML('reason_'.$line->ID); ?></td>
							</tr>

							<?php
						}
						?>
					</table>
					<br />

					<input class="greySubmit" type="button" name="back" value="Back" onclick="window.self.location.href = 'despatches.php';" />
					<input class="submit" type="submit" name="continue" value="Continue" />

					<?php
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