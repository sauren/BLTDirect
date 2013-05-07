<?php
require_once('lib/common/appHeader.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Where's My Country?</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="css/popups.css" rel="stylesheet" type="text/css" />
</head>
<body>
	<img src="images/popups/logo_blt_1.jpg" width="138" height="50" />
	<div id="Close"><a href="javascript:window.self.close();">Close Window</a></div>
	<div id="Content">
		<h1>Where is My Country? </h1>
		<p>Our online shop is configured to calculate shipping costs for those countries we deliver to regularly. </p>
		<p>Your custom is important to us. If your country is not listed on our site please call us on <strong><?php echo Setting::GetValue('telephone_sales_hotline'); ?></strong>.</p>
		<p>When contacting BLT Direct regarding shipping costs please have your full delivery information ready. </p>
	</div>
</body>
</html>
<?php include('lib/common/appFooter.php'); ?>