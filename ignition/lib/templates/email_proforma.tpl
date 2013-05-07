<p>Please find below a pro forma invoice as requested.</p>

[ATTACHMENTS]

[HANDLING]

<table border="0" width="100%" cellspacing="0">
	<tr>
		<td width="50%">
			<table cellpadding="0" cellspacing="0" border="0" style="border:1px solid #aaaaaa;" width="100%">
				<tr>
					<td valign="top" style="padding:10px;" width="50%"><p>
						<strong>Billing Address:</strong><br />
						[BILLTO]</p>

					</td>
					<td valign="top" style="padding:10px; background-color:#eeeeee;"><p>
						<strong>Shipping Address:</strong><br />
						[SHIPTO]</p>
					</td>
				</tr>
			</table>
		</td>
		<td align="right">
            <table cellpadding="3" cellspacing="0" border="0">
                <tr>
                    <td valign="top">Pro Forma Ref:</td><td>[PROFORMA_REF]</td>
                </tr>
                <tr>
                    <td valign="top">Pro Forma Date:</td><td>[PROFORMA_DATE]</td>
                </tr>
                <tr>
                    <td valign="top">Customer:</td><td>[CUSTOMER_NAME]</td>
                </tr>
                <tr>
					<td valign="top">Customer Ref:</td><td>[CUSTOMER_ID]</td>
				</tr>
            </table>
		</td>
	</tr>
</table>

<br />
<br />
<table width="100%" cellspacing="0" cellpadding="5" class="order">
	<tr>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Qty</th>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Product</th>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Quickfind</th>
		<th align="right" style="border-bottom:1px solid #FA8F00;">Price</th>
		<th align="right" style="border-bottom:1px solid #FA8F00;">Discount Price</th>
		<th align="right" style="border-bottom:1px solid #FA8F00;">Line Total</th>
	</tr>
				[PROFORMA_LINES]
				<tr>
		<td colspan="5" align="right">Sub Total:</td>
		<td align="right">[SUBTOTAL]</td>
	</tr>
</table>
<br />
<table border="0" width="100%" cellpadding="0" cellspacing="0">
	<tr>
	  <td width="50%" valign="top">
		<p>Cart Weight: [PROFORMA_WEIGHT]Kg.<br /><span class="smallGreyText">(Approx.)</span></p>
		<p><strong>Please send cheque to:</strong></p>
		<p>Ellwood Electrical Ltd.<br>Unit 9, The Quadrangle, Nacton Road,<br>Ipswich, Suffolk IP3 9QR</p>
	  </td>
	  <td width="50%" valign="top" align="right">
			<table border="0" cellpadding="5" cellspacing="0" class="order">
				<tr>
					<th colspan="2" align="left" style="border-bottom:1px solid #FA8F00;">Tax &amp; Shipping</th>
				</tr>
				<tr>
				  <td>Delivery Option:</td>
					<td align="right">
						[DELIVERY]</td>
				</tr>
				<tr>
					<td>Shipping:</td>
					<td align="right">[SHIPPING]</td>
				</tr>
				<tr>
					<td>Discount:</td>
					<td align="right">[DISCOUNT]</td>
				</tr>
				<tr>
					<td>Net:</td>
					<td align="right">[NET]</td>
				</tr>
				<tr>
					<td>VAT:</td>
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