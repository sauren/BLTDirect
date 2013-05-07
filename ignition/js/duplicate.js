var responseHandle = function(response) {
    var lines = document.getElementById("lines");
    var table = null;
    var rows = null;
    var cols = null;
    var input = null;

    if(lines) {
	    rows = response.split("\n");

	    table = '<table cellspacing="0" class="myAccountOrderHistory">';
	    table += '<tr>';
	    table += '<th>&nbsp;</th>';
	    table += '<th>Product</th>';
	    table += '<th>Quantity</th>';
	    table += '<th style="text-align: right; white-space: nowrap;">Item Price</th>';
	    table += '</tr>';

	    for(var i=1; i<rows.length-1; i++) {
	        cols = rows[i].split(",");

			table += '<tr>'
			table += '<td>';
			
			if(cols[2] > 0) {
				table += '<input type="checkbox" checked="checked" name="lid_' + cols[0] + '" value="' + cols[0] + '" />';
			} else {
				table += '';
			}

			table += '</td>';

			for(var j=1; j<cols.length; j++) {
				if(j == 2){
					if(cols[j] > 0) {
						input = '<select name="quantity_' + cols[0] + '">';

						for(var x=1; x<=cols[j]; x++) {
							if(x==cols[j]) {
								input += '<option selected="selected" value="' + x + '">' + x + '</option>';
							}
							else {
								input += '<option value="' + x + '">' + x + '</option>';
							}
						}

						for(var x=parseInt(cols[j]) + 1; x<=25; x++) {
							input += '<option value="' + x + '">' + x + '</option>';
						}

						input += '</select>';
					} else {
						input = '';
					}

					table += '<td>' + input + '</td>';
				} else if(j == 3) {
					if(cols[j] > 0) {
						table += '<td style="text-align: right;">&pound;' + cols[j] + '</td>';
					} else {
						table += '<td></td>';
					}
				} else {
					table += '<td>' + cols[j] + '</td>';
				}
			}

			table += '</tr>';
	    }

	    table += '</table>';
	    table += '<div style="text-align: right; padding: 10px;"><input type="submit" value="Duplicate" class="submit" name="action" /></div>';

	    lines.innerHTML = table;
    }
}

var getDuplicationLines = function(orderid) {
    var req = new HttpRequest();
    req.setHandlerResponse(responseHandle);
    req.post('ignition/lib/ajax/duplicate.php', "orderid="+orderid);
}