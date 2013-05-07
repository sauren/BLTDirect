<?php
$defaultSession = 'User';
$defaultDevice = 'Desktop';

$portals = array();
$portals['barcode'] = array('Device' => 'Mobile');
$portals['supplier'] = array('Session' => 'Supplier');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Portal Hub</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<style>
		body{
			margin:0;
			padding: 0;
			background-color: #DCE3EE;
		}
		body,td,th {
			font-family: Verdana, Arial, Helvetica, sans-serif;
			font-size: 11px;
			color: #000000;
		}
		.clear{
			clear:both;
		}
		.float-left{
			float:left;
		}
		.float-right{
			float:right;
		}
		img{
			border:none;
		}
		a {
			text-decoration: none;
			color: #2A599D;
		}
		p{
			margin:0;
			padding:0;
			padding-bottom:15px;
		}
		h1 {
			font-weight: bold;
			font-size: 16px;
			font-family: arial, sans-serif;
			text-align: center;
		}
		h1 a {
			color: #000;
		}
		h1 a:hover {
			color: #000;
			text-decoration: underline;
		}
		#Header{
			height:138px;
			background-image:url(../images/template/bg_1.jpg);
			background-repeat:repeat-x;
			background-color:#DCE3EE;
		}
		#Header .logo{
			float:left;
			margin-bottom:2px;
		}
		#NavBar{
			margin:0 auto auto auto;
			clear:both;
			height:26px;
			color:#ffffff;
			font-family:Arial, Helvetica, sans-serif;
			font-size:14px;
			font-weight:bold;
			font-style:italic;
			text-align:left;
			padding:5px 0 0 45px;
		}
		#Body {
			margin: 0 35px;
		}
		#Body .portal {
			display: inline-block;
			background-color: #fff;
			width: 180px;
			height: 250px;
			margin: 10px;
			-webkit-border-radius: 10px;
			-moz-border-radius: 10px;
			border-radius: 10px;
			-webkit-box-shadow: 5px 5px 20px rgba(0, 0, 0, 0.5);
			-moz-box-shadow: 5px 5px 20px rgba(0, 0, 0, 0.5);
			box-shadow: 5px 5px 20px rgba(0, 0, 0, 0.5);
 			padding: 10px;
		}
		#Body .portal-old {
			background-color: #ccc;
		}
		#Body .portal span.highlight {
			color: #2A599D;
			font-weight: bold;
		}
	</style>
</head>
<body>
	<div id="Wrapper">
		<div id="Header">
			<img src="../images/template/logo_blt_1.jpg" width="185" height="70" class="logo" alt="BLT Direct" />
			<div id="NavBar">Portal Hub</div>
		</div>
		<div id="Body">

			<?php
			$dir = './';

			if(is_dir($dir)) {
				if($dh = opendir($dir)) {
					while(($file = readdir($dh)) !== false) {
						if(is_dir($dir.$file)) {
							if(substr($file, 0, 1) != '.') {
								?>

								<div class="portal">
									<h1><a href="<?php echo $file; ?>/"><?php echo ucfirst($file); ?> Portal</a></h1>

									<p>Logon Requirements:<br /><span class="highlight"><?php echo (isset($portals[$file]) && isset($portals[$file]['Session'])) ? $portals[$file]['Session'] : $defaultSession; ?> Account</span></p>

									<p>Preferred Device:<br /><span class="highlight"><?php echo (isset($portals[$file]) && isset($portals[$file]['Device'])) ? $portals[$file]['Device'] : $defaultDevice; ?></span></p>
								</div>

								<?php
							}
						}
					}

					closedir($dh);
				}
			}
			?>

		</div>
	</div>
</body>
</html>