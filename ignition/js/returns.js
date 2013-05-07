function returns_get_lines(orderid){

	var responseHandler = function(s){
		var lines = document.getElementById("lines");
		//Format cvs
		rows = s.split("\n");
		table = "<table cellspacing=\"0\" class=\"myAccountOrderHistory\">\n";
		table += "<tr>\n";
		table += "<th>&nbsp;</th>\n";
		cols = rows[0].split(",");
		for(var i=1; i<cols.length; i++)
		table += "<th>"+ cols[i] + "</th>";
		table += "</tr>\n";
		for(var i=1; i<rows.length-1; i++){
			table += "<tr height=\"11\">\n";
			cols = rows[i].split(",");
			table += '<td style="padding-left: 13px"><input type="radio" name="lid" value="';
			table += cols[0] + '" onclick="set_line(' + cols[0] + ');" /></td>';

			for(var j=1; j<cols.length; j++) {
				if(j == 2){
					var input = "<select name=\"quantity_" + cols[0] + "\" onfocus=\"set_quantity(this.value);\">";
					input += "<option value=\"1\" selected=\"selected\">" + 1 + "</option>";
					for(var x=2; x<=cols[j]; x++){
						input += "<option value=\"" + x + "\">" + x + "</option>";
					}
					input += "</select>";
					table += "<td>" + input + "</td>";
				} else {
					table += "<td>"+ cols[j] + "</td>";
				}
			}
			table += "</tr>\n";
		}
		table += "<tr><td style=\"color: #A81124; padding: 10px 30px; border-bottom: none;\" colspan=\"4\"><br/><input disabled=\"disabled\" class=\"submit\" type=\"submit\" name=\"proceed\" id=\"proceed-button\" value=\"Proceed\" tabindex=\"1\"/><br /><br /><p>Please ensure you select the <strong>correct</strong> quantity of lamps to be returned.</strong></p></td></tr>\n";
		table += "</table>\n";
		table += "</div>\n";

		// Display table
		lines.innerHTML = "";
		lines.innerHTML = table;
	}

	var req = new HttpRequest()
	req.url = 'ignition/lib/ajax/returns.php'
	req.method = 'POST'
	req.setHandlerResponse(responseHandler)
	req.post(req.url, "orderid="+orderid);

	document.getElementById('step_1_1').style.fontWeight = "normal";
	document.getElementById('step_1_2').style.fontWeight = "bold";
	document.getElementById('step_1_3').style.fontWeight = "normal";
	document.getElementById('step_1_4').style.fontWeight = "normal";	
}

function set_line(lineid) {
	document.getElementById('goto_2').style.visibility = "visible";
	
	document.getElementById('step_1_1').style.fontWeight = "normal";
	document.getElementById('step_1_2').style.fontWeight = "normal";
	document.getElementById('step_1_3').style.fontWeight = "bold";
	document.getElementById('step_1_4').style.fontWeight = "normal";

	document.getElementById('proceed-button').removeAttribute('disabled');
}

function set_quantity(quantity) {
	document.getElementById('step_1_1').style.fontWeight = "normal";
	document.getElementById('step_1_2').style.fontWeight = "normal";
	document.getElementById('step_1_3').style.fontWeight = "normal";
	document.getElementById('step_1_4').style.fontWeight = "bold";
}

function step_2_focus(e) {
	if(e == 'product'){
		var td = document.getElementById('components_table').getElementsByTagName('td');
		var th = document.getElementById('components_table').getElementsByTagName('th');
		var tick = document.getElementById('components_table').getElementsByTagName('input');
		var select = document.getElementById('components_table').getElementsByTagName('select');
		for(i in td){
			//alert(td[i].innerHTML);
			try{
				if(td[i].innnerHTML != 'undefined') td[i].style.color = '#999';
			} catch(e) {}
		}
		for(i in th){
			try{
				th[i].style.color = '#999';
			} catch(e){}
		}
		for(i in tick){
			try{
				tick[i].disabled = 'disabled';
			} catch(e){}
		}
		for(i in select){
			try{
				select[i].disabled = 'disabled';
			} catch(e){}
		}
	} else if(e == 'components'){
		var td = document.getElementById('components_table').getElementsByTagName('td');
		var th = document.getElementById('components_table').getElementsByTagName('th');
		var tick = document.getElementById('components_table').getElementsByTagName('input');
		var select = document.getElementById('components_table').getElementsByTagName('select');
		for(i in td){
			try{
				td[i].style.color = '#000';
			} catch(e){}
		}
		for(i in th){
			try{
				th[i].style.color = '#000';
			} catch(e){}
		}
		for(i in tick){
			try{
				tick[i].disabled = '';
			} catch(e){}
		}
		for(i in select){
			try{
				select[i].disabled = '';
			} catch(e){}
		}
	}
}