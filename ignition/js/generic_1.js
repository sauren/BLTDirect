function clearField(field){
	document.getElementById(field).value = "";
}

function popUrl(url, width, height){
	var winName = "popUrl";
	var features = 'status=yes,scrollbars=yes,resizable=yes,width='+width+',height='+height;
	var win1 = window.open(url,winName,features);
	win1.focus();
}

function closeReloadParent(){
    window.opener.location.reload(true);
    window.self.close();
}

function swapImage() {
  var args = swapImage.arguments;
  //window.top.frames['i_content'].frames['i_resizer'].document
  for(var i=0; i<args.length; i+=3){
  	if(!args[i] || args[i] == '') args[i] = document;
  	args[i].getElementById(args[i+1]).src = args[i+2];
  }
}

function setClassName(objId, className) {
	objId.className = className;
}

function showStatus(msgStr) { //v1.0
  status=msgStr;
  document.statusValue = true;
}

function convertTime(num){
	num = parseInt(num/60) + ":" + seconds(secs%60);
	return num;
}

function seconds(data){
	if(data<10){data = "0" + data;}
	return data;
}

function systemLogout(auto){
	var sure = auto;
	if(!sure){
		sure = confirm("Are you sure you want to exit Ignition?");
	}
	if(sure){
		window.top.location.href = "../ignition.php?serve=session&action=logout";
	}
}

function printDisplay(){
	var printing = top.frames['i_content'].frames['i_content_display'];
	printing.focus();
	printing.print();
}

function confirmRequest(url, text){
	var sure = confirm(text);
	if(sure){
		window.self.location.href=url;
	}
}

function confirmText(text){
	return confirm(text);
}

function popFindUser(id,name){
	window.opener.document.getElementById('userStr').value = name;
	window.opener.document.getElementById('user').value = id;
	window.self.close();
}
function popFindPerson(id,name){
	window.opener.document.getElementById('personStr').value = name;
	window.opener.document.getElementById('person').value = id;
	window.self.close();
}
function popFindProduct(id,name){
	window.opener.document.getElementById('name').value = name;
	window.opener.document.getElementById('product').value = id;
	window.self.close();
}
function popFind(name, val){
    window.opener.document.getElementById(name).value = val;
    window.self.close();
}
function popFindProductAssociation(id, field){
	window.opener.document.getElementById(field).value = id;
	window.self.close();
}

function getRealLeft(obj){
	var xPos = obj.offsetLeft;
	var tempObj = obj.offsetParent;
	while (tempObj != null) {
		xPos += tempObj.offsetLeft;
		tempObj = tempObj.offsetParent;
	}
	return xPos;
}

function getRealTop(obj){
	var yPos = obj.offsetTop;
	var tempObj = obj.offsetParent;
	while (tempObj != null) {
		yPos += tempObj.offsetTop;
		tempObj = tempObj.offsetParent;
	}
	return yPos;
}

function iTextSelect(obj, iStart, iEnd){
	switch(arguments.length) {
	   case 1:
		   obj.select();
		   break;
		case 2:
			iEnd = obj.value.length;
		case 3:
			var oRange = obj.createTextRange();
		   oRange.moveStart("character", iStart);
		   oRange.moveEnd("character", -obj.value.length + iEnd);
		   oRange.select();
   }
}

function copy(url) {
	window.clipboardData.setData('Text', url);
	alert("URL copied to clipboard:\n\n" + url);
}

function checkUncheckAll(theElement, startsWith) {
	var theForm = theElement.form, z = 0;
	for(z=0; z<theForm.length;z++){
		if(theForm[z].type == 'checkbox' && theForm[z].name != 'checkall'){
			if(startsWith) {
				if((theForm[z].name.length >= startsWith.length) && (theForm[z].name.substr(0, startsWith.length) == startsWith)) {
					theForm[z].checked = theElement.checked;
				}
			} else {
				theForm[z].checked = theElement.checked;
			}
		}
	}
}

function number_format(number, decimals, dec_point, thousands_sep) {
    var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
    var d = dec_point == undefined ? "." : dec_point;
    var t = thousands_sep == undefined ? "," : thousands_sep, s = n < 0 ? "-" : "";
    var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
    
    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}