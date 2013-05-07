<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style type="text/css">
<!--
body,td,th {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #000000;
}
body {
	background-color: #FFFFFF;
	margin: 0;
}
.style1 {color: #FA8F00}
.style2 {	font-family: Arial, Helvetica, sans-serif;
	font-weight: bold;
}
.style4 {color: #FA8F00; font-weight: bold; }
h1{
		font:"Myriad", Arial, Helvetica, sans-serif;
		font-size:18px;
		font-weight:normal;
		margin:0;
		padding:0;
	}
a {
	color: #FA8F00;
	text-decoration: none;
}
a:hover {
	text-decoration: underline;
}
-->
</style><title>Purchase Order</title></head>
<body>
<p>&nbsp;</p>
<table border="0" width="100%" cellspacing="0">
	<tr>
	  <td width="50%">
        <h1>Purchase Order</h1>
        <br>
        <table cellpadding="0" cellspacing="0" border="0" style="border:1px solid #aaaaaa;" width="100%">
          <tr>
            <td valign="top" style="padding:10px;" width="50%"><p> <strong>Supplier Details:</strong><br />
        [SUPPLIER_DETAILS]</p></td>
        <td valign="top" style="padding:10px;" width="50%"><p> <strong>Shipping Details:</strong><br />
        [SHIPTO]</p></td>
          </tr>
        </table>
        <br>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#eeeeee" style="border:1px solid #aaaaaa;">
              <tr>

                <td valign="top" style="padding:10px;" width="25%"><p> <strong>Purchase Order #: </strong><br>
                  [PURCHASE_REF]<br>
                  </p></td>
                <td valign="top" style="padding:10px;" width="25%"><strong>Purchase Order Date: </strong><br>
                [PURCHASE_DATE]</td>
              </tr>
        </table>
      </td>
		<td align="right"><p><img src="http://www.bltdirect.com/images/logo_blt_3.gif" width="168" height="69"></p>
	    <p>Ellwood Electrical Ltd.<br>
	      Unit 9, The Quadrangle, Nacton Road,<br>
	      Ipswich, Suffolk IP3 9QR</p>
	    <p>Sales Hotline +44 (0)1473 716 418<br>
	    Customer Services +44 (0)1473 559 501<br>
Fax +44 (0)1473 718 128<br>
Email sales@bltdirect.com</p>
	    </td>
	</tr>
</table>
			<br />
			<table width="100%" cellspacing="0" cellpadding="5" class="order">
				<tr>
					<th align="left" style="border-bottom:1px solid #FA8F00;">Qty</th>
					<th align="left" style="border-bottom:1px solid #FA8F00;">Product</th>
					<th align="left" style="border-bottom:1px solid #FA8F00;">Quickfind</th>
					<th align="left" style="border-bottom:1px solid #FA8F00;">Cost</th>
					<th align="left" style="border-bottom:1px solid #FA8F00;">Line Cost</th>
				</tr>
							[LINES]
<tr>
    <td colspan="4" align="right">Sub Total:</td>
    <td align="right">[SUBTOTAL]</td>
  </tr>
</table>
			<br />
            <table border="0" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="right" valign="top"><table border="0" cellpadding="5" cellspacing="0" class="order">
                    <tr>
                      <th colspan="2" align="left" style="border-bottom:1px solid #FA8F00;">Tax</th>
                    </tr>
					<tr>
                      <td>Net:</td>
                      <td align="right">[NET]</td>
                    </tr>
                    <tr>
                      <td>Tax:</td>
                      <td align="right">[TAX]</td>
                    </tr>
                    <tr>
                      <td>Total:</td>
                      <td align="right">[TOTAL]</td>
                    </tr>
                  </table>
                 </td>
              </tr>
            </table>
            <br />
			</td>
			</tr>
			<tr><td align="left" valign="top">[NOTICES]</td></tr>

</table>
            <br>
            <table width="100%"  border="0" cellspacing="0" cellpadding="5">
              <tr>
                <td><a href="http://www.bltdirect.com" style="text-decoration:none; color:#000000"><span class="style2">www.<span class="style1">bltdirect</span>.com</span></a></td>
                <td align="right"><span class="style4">Tel.</span><span class="style2"> +44 (0) 1473 716 418 </span></td>
              </tr>
            </table>
<p>&nbsp;</p>
</body>
</html>