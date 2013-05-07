<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<style type="text/css">
		<!--
		body, td, th {
			font-family: Verdana, Arial, Helvetica, sans-serif;
			font-size: 12px;
			color: #000000;
		}
		body {
			background-color: #FFFFFF;
			margin: 0;
		}
		.style1 {
			color: #D30000;
		}
		.style2 {
			font-family: Arial, Helvetica, sans-serif;
			font-weight: bold;
		}
		.style4 {
			color: #D30000;
			font-weight: bold;
		}
		.order td {
			border-bottom:1px dashed #aaaaaa;
		}
		h1{
			font: Arial, Helvetica, sans-serif;
			font-size:18px;
			font-weight:normal;
			margin:0;
			padding:0;
		}
		h2{
			font: Arial, Helvetica, sans-serif;
			font-size:14px;
			font-weight:bold;
			margin:0;
			padding: 10px 0 5px 0;
		}
		h3{
			font: Arial, Helvetica, sans-serif;
			font-size:12px;
			font-weight:bold;
			margin:0;
			padding: 10px 0 5px 0;
		}
		-->
	</style>
	<title>Supplier Return Request List</title>
</head>
<body>

<table border="0" width="100%" cellspacing="0">
	<tr>
		<td width="50%" align="left" valign="top">

        	<table cellpadding="0" cellspacing="0" border="0" style="border:1px solid #aaaaaa;" width="100%">
				<tr>
					<td valign="top" style="padding:10px; background-color:#eeeeee;">
						<p><strong>Supplier Details:</strong><br />[SUPPLIER_ADDRESS]</p>
					</td>
				</tr>
			</table>

        </td>
        <td width="50%" align="right" valign="top"></td>
	</tr>
</table>
<br />

<table width="100%" cellspacing="0" cellpadding="5" class="order">
	<tr>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Return Request #</th>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Authorisation Number</th>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Quantity</th>
        <th align="left" style="border-bottom:1px solid #FA8F00;">Description</th>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Quickfind</th>
	</tr>

	[SUPPLIER_RETURN_REQUEST_LINES]

</table>

</body>
</html>