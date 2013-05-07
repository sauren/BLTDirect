<table border="0" width="100%" cellspacing="0">
	<tr>
		<td width="50%" align="left" valign="top">

        	<table cellpadding="0" cellspacing="0" border="0" style="border:1px solid #aaaaaa;" width="100%">
				<tr>
					<td valign="top" style="padding:10px; background-color:#eeeeee;">
						<p><strong>Supplier Details:</strong><br />[SUPPLIER_DETAILS]</p>
					</td>
				</tr>
			</table>
			<br />

            <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#eeeeee" style="border:1px solid #aaaaaa;">
				<tr>
					<td valign="top" style="padding:10px;" width="50%"><strong>Invoice Query #:</strong><br />[SUPPLIER_QUERY_ID]</td>
					<td valign="top" style="padding:10px;" width="50%"><strong>Invoice Query Date:</strong><br />[SUPPLIER_QUERY_DATE]</td>
				</tr>
                <tr>
					<td valign="top" style="padding:10px;" width="50%"><strong>Invoice Reference:</strong><br />[SUPPLIER_QUERY_INVOICE_REFERENCE]</td>
					<td valign="top" style="padding:10px;" width="50%"><strong>Invoice Date:</strong><br />[SUPPLIER_QUERY_INVOICE_DATE]</td>
				</tr>
			</table>

        </td>
        <td width="50%" align="right" valign="top"></td>
	</tr>
</table>
<br />

<table width="100%" cellspacing="0" cellpadding="5" class="order">
	<tr>
        <th align="left" style="border-bottom:1px solid #FA8F00;">Quantity</th>
        <th align="left" style="border-bottom:1px solid #FA8F00;">Description</th>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Quickfind</th>
		<th align="right" style="border-bottom:1px solid #FA8F00;">PO Price</th>
		<th align="right" style="border-bottom:1px solid #FA8F00;">Charge Received</th>
		<th align="right" style="border-bottom:1px solid #FA8F00;">Difference</th>
		<th align="right" style="border-bottom:1px solid #FA8F00;">Total</th>
	</tr>

	[SUPPLIER_QUERY_LINE]

</table>