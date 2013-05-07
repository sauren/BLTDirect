<?php
    require_once('lib/common/appHeader.php');
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

    $resetValid = true;
    $passwordComplete = false;

    $id = $_REQUEST['cid'];
    $token = $_REQUEST['token'];

    $valid = 0;
    if(isset($_REQUEST['valid']) && !empty($_REQUEST['valid'])){
        $valid = $_REQUEST['valid'];
    }

    $customer = new Customer;
    $customer->Get($id);

    $validToken = $customer->ValidateToken($token, $valid);

    if($action == 'reset'){
        if(!$validToken){
            $resetValid = false;
        }
    } else if($action == "confirmpassword"){
        $passwordComplete = true;
    } else {
        redirect("Location: gateway.php");
    }

    $form = new Form($_SERVER['PHP_SELF']);
    $form->Icons['valid'] = '';
    $form->AddField('action', 'Action', 'hidden', 'reset', 'alpha', 5, 5);
    $form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
    $form->AddField('customerid', 'CustomerID', 'hidden', $id , 'true', 'numeric_unsigned', 1, 11);
    $form->AddField('valid', 'Valid', 'hidden',  $validToken , 'true', 'boolean', 4, 5);
    $form->AddField('password', 'New Password:', 'password', '', 'password', PASSWORD_LENGTH_CUSTOMER, 100);
    $form->AddField('confirmPassword', 'Reenter New Password:', 'password', '', 'password', PASSWORD_LENGTH_CUSTOMER, 100);

    if(strtolower(param('confirm', '')) == "true"){
        $form->Validate();
        $customerID = "";
        if(isset($_REQUEST['customerid']) && !empty($_REQUEST['customerid'])){
            $customerID = $_REQUEST['customerid'];
        }
        $customer = new Customer();
        $customer->get($customerID);

        if($form->Valid){
            $customer->SetPassword($form->GetValue('password'));
            $customer->Update();
            
            $customer->ResetToken();

            $session->Login($customer->Username, $form->GetValue('password'));
            redirect("Location: resetPassword.php?action=confirmpassword&resetconfirm=true");
            exit;
        }
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
<title>BLT Direct - Forgotten Password</title>
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
	<!-- InstanceBeginEditable name="head" -->
    <script type="text/javascript" src="/js/swfobject.js"></script>

   
    
	<meta name="Keywords" content="light bulbs, light bulb, lightbulbs, lightbulb, lamps, fluorescent, tubes, osram, energy saving, sylvania, philips, ge, halogen, low energy, metal halide, candle, dichroic, gu10, projector, blt direct" />
	<meta name="Description" content="We specialise in supplying lamps, light bulbs and fluorescent tubes, Our stocks include Osram,GE, Sylvania, Omicron, Pro lite, Crompton, Ushio and Philips light bulbs, " />
	<!-- InstanceEndEditable -->
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
                <h1>BLT Direct Password Assistance</h1>
                      </br>
                <?php if(param('resetconfirm')){?>
                    <p class="rpHeading">Password Confirmation</p>
                    <p>Your password has successfully been updated and is ready to use</p>
                    

                <?php } else {  ?>


                    <?php if($resetValid){ ?>
                    <p class="rpHeading">Create Your New Passowrd</p>
                    <p>We'll ask you for this password when you place an order, check on an order's status, and access other account information.</p>

                        <?php if(!$form->Valid){
                                echo $form->GetError();
                                echo "<br>";
                            }

                            echo $form->Open();
                            echo $form->GetHtml('action');
                            echo $form->GetHtml('confirm');
                            echo $form->GetHtml('customerid');
                            echo $form->GetHtml('valid');
                        ?>

                        <table width="100%" cellspacing="0" class="form">
                            <tr>
                                <th colspan="2">Reset Your Password</th>
                            </tr>
                            <tr>
                                <td> <?php echo $form->GetLabel('password'); ?> </td>
                                <td> <?php echo $form->GetHtml('password'); ?> <?php echo $form->GetIcon('password'); ?> </td>
                            </tr>
                            <tr>
                                <td> <?php echo $form->GetLabel('confirmPassword'); ?> </td>
                                <td> <?php echo $form->GetHtml('confirmPassword'); ?> <?php echo $form->GetIcon('confirmPassword'); ?> </td>
                            </tr>
                        </table>
                            </br>
                            <p>
                                <input type="submit" class="submit" value="Reset Password" \ />
                            </p>
                            <?php echo $form->Close(); ?>

                        <br/>
                        <p><strong>Secure Password Tips:</strong></p>
                        
                        <ul class="securePassowrdtips">
                            <li>Use at least 8 characters, a combination of numbers and letters is best.</li>
                            <li>Do not use dictonary words, your name, e-mail address, or other personal information that can easily be obtained</li>
                            <li>Do not use the same password for multiple online accounts</li>
                        </ul>

                    <?php } else { ?>

                        <p class="rpHeading">Password Assistance Errors</p>
                        <p><strong>We are unable to reset your password due to the following: -</strong></p>

                        <ul>
                        <?php if(!$tokenValidation){ ?>
                            <li>The reset token used is invalid</li>
                        <?php }?>

                        <?php if(!$tokenDateValidation){ ?>
                            <li>The reset token has expired or has already been used</li>
                        <?php }?>
                        </ul>
                    <?php }
                } ?>


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