<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Alert.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Bubble.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryBreadCrumb.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductCookie.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductPrice.php");

function getCategories($id) {
	$items = array($id);
	
	$data = new DataQuery(sprintf("SELECT Category_Parent_ID FROM product_categories WHERE Category_ID=%d", mysql_real_escape_string($id)));
	if($data->TotalRows > 0) {
		if($data->Row['Category_Parent_ID'] > 0) {
			$items = array_merge($items, getCategories($data->Row['Category_Parent_ID']));
		}
	}
	$data->Disconnect();
	
	return $items;
}

$product = new Product();

if(param('pid')) {
	$product->ID = str_replace($GLOBALS['PRODUCT_PREFIX'], '', param('pid'));

	if(!is_numeric($product->ID)) {
		redirect("Location: index.php");
	}

	if(!$product->Get($product->ID)) {
		redirect("Location: index.php");
	}

	if(($product->IsActive == 'N') || ($product->IsDemo == 'Y')) {
		redirect("Location: index.php");
	}
} else {
	redirect("Location: index.php");
}

$category = new Category();
$category->ID = 0;

if(id_param('cat')){
	$category->Get(id_param('cat'));
	$breadCrumb = new CategoryBreadCrumb();
	$breadCrumb->Get($category->ID, true);
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('pid', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('cat', 'Category ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('type', 'Type', 'select', '', 'paragraph', 1, 120);
$form->AddOption('type', '', '');
$form->AddOption('type', 'No image', 'No image');
$form->AddOption('type', 'Incorrect grammar', 'Incorrect grammar');
$form->AddOption('type', 'Other', 'Other');
$form->AddField('description', 'Description', 'textarea', '', 'anything', null, null, true, 'rows="5" style="width: 300px; font-family: arial, sans-serif;"');

if(param('confirm')) {
	if($form->Validate()) {
		$alert = new Alert();
		$alert->referenceId = $product->ID;
		$alert->owner = 'Product';
		$alert->type = $form->GetValue('type');
		$alert->description = $form->GetValue('description');
		$alert->add();

		redirectTo(sprintf('productReport.php?pid=%d&cat=%d&status=sent', $product->ID, $category->ID));
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Report A Site Error</title>
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
              		<h1>Report A Site Error</h1>
					<p class="breadcrumb"><a href="/">Home</a> <?php echo isset($breadCrumb) ? $breadCrumb->Text : ''; ?> / <a href="/product.php?pid=<?php echo $product->ID; ?>&amp;cat=<?php echo $category->ID; ?>&amp;nm=<?php echo urlencode($product->MetaTitle); ?>"><?php echo $product->Name; ?></a></p>

					<?php
					if(param('status')) {
						$bubble = new Bubble('Incident Submitted', sprintf('Thank you for raising a report for \'<strong>%s</strong>\' for which we will address shortly.<br /><a href="/product.php?pid=%d&cat=%d&nm=%s">Click here</a> to return to this product.', $product->Name, $product->ID, $category->ID, urlencode($product->MetaTitle)));	
						
						echo $bubble->GetHTML();
						echo '<br />';
					}
					
					if(!$form->Valid){
						echo $form->GetError();
						echo '<br />';
					}
					
					echo $form->Open();
					echo $form->GetHtml('confirm');
					echo $form->GetHtml('pid');
					echo $form->GetHtml('cat');
					?>

					<table style="width:100%; border:0px;" cellpadding="0" cellspacing="0" class="bluebox">
						<tr>
							<td>
							
								<h3 class="blue">Report Form</h3>
								<p class="blue">Please complete the fields below. Required fields are marked with an asterisk (*).</p>

								<p>Type <?php echo $form->GetIcon('type'); ?><br /><?php echo $form->GetHTML('type'); ?></p>
								<p>Description <?php echo $form->GetIcon('description'); ?><br /><?php echo $form->GetHTML('description'); ?></p>
								<p><input name="Submit" type="submit" class="submit" id="Send" value="Send" /></p>

							</td>
						</tr>
					</table>

					<?php echo $form->Close(); ?>

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