function clearField(field){
	document.getElementById(field).value = "";
}

function popUrl(url, width, height){
	var winName = "popUrl";
	var features = 'status=yes,scrollbars=yes,width='+width+',height='+height;
	var win1 = window.open(url,winName,features);
	win1.focus();
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

	/*
		getRealLeft([object])
		returns the absolute left position of an object in pixels.
	*/
	function getRealLeft(obj){
		var xPos = obj.offsetLeft;
		var tempObj = obj.offsetParent;
		while (tempObj != null) {
			xPos += tempObj.offsetLeft;
			tempObj = tempObj.offsetParent;
		}
		return xPos;
	}
	
	/*
		getRealTop([object])
		returns the absolute top position of an object in pixels.
	*/
	function getRealTop(obj){
		var yPos = obj.offsetTop;
		var tempObj = obj.offsetParent;
		while (tempObj != null) {
			yPos += tempObj.offsetTop;
			tempObj = tempObj.offsetParent;
		}
		return yPos;
	}
	
	/*
		iTextSelect([object], [int], [int])
		can be used with 1, 2 or 3 arguments
		selects text within an object
	*/
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