<p>The following order lines which were backordered have been reassigned and are no longer required for despatch.</p>

<table border="0" width="100%" cellspacing="0">
	<tr>
		<td width="50%" align="left" valign="top">

			<table cellpadding="0" cellspacing="0" border="0" style="border:1px solid #aaaaaa;">
				<tr>
					<td valign="top" style="padding:10px;" width="50%">
						<p><strong>Billing Address:</strong><br />[ORDER_BILLING_ADDRESS]</p>
					</td>
					<td valign="top" style="padding:10px; background-color:#eeeeee;">
						<p><strong>Shipping Address:</strong><br />[ORDER_SHIPPING_ADDRESS]</p>
					</td>
				</tr>
			</table>

		</td>
		<td width="50%" align="right" valign="top">

			<table cellpadding="3" cellspacing="0" border="0" style="border:1px solid #aaaaaa;">
				<tr>
					<td nowrap="nowrap" valign="top" style="padding: 5px; background-color:#eeeeee;" align="right"><strong>Order Ref:</strong></td>
					<td nowrap="nowrap" valign="top" style="padding: 5px;">[ORDER_REFERENCE]</td>
				</tr>
				<tr>
					<td nowrap="nowrap" valign="top" style="padding: 5px; background-color:#eeeeee;" align="right"><strong>Order Date:</strong></td>
					<td nowrap="nowrap" valign="top" style="padding: 5px;">[ORDER_DATE]</td>
				</tr>
				<tr>
					<td nowrap="nowrap" valign="top" style="padding: 5px; background-color:#eeeeee;" align="right"><strong>Customer:</strong></td>
					<td nowrap="nowrap" valign="top" style="padding: 5px;">[CUSTOMER_NAME]</td>
				</tr>
				<tr>
					<td nowrap="nowrap" valign="top" style="padding: 5px; background-color:#eeeeee;" align="right"><strong>Customer Ref:</strong></td>
					<td nowrap="nowrap" valign="top" style="padding: 5px;">[CUSTOMER_ID]</td>
				</tr>
			</table>

		</td>
	</tr>
</table>
<br />

<table width="100%" cellspacing="0" cellpadding="5" class="order">
	<tr>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Qty</th>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Product</th>
		<th align="left" style="border-bottom:1px solid #FA8F00;">Quickfind</th>
	</tr>

	[ORDER_LINES]

</table>