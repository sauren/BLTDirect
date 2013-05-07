function defWin(){
	this.opened = "60%,*";
	this.closed = "*,30";
	this.frameID = "i_frmSet_3";
	this.layoutType = "rows";
	this.tabs = new Object();
	this.currentTab;
	this.target;
	this.resizer = new Array('','hlpWin_resizer','../images/hlpWin_btn_1.gif','../images/hlpWin_btn_2.gif');
}

defWin.prototype.init = function(){
	this.frm = window.top.document.getElementById(this.frameID);
}

defWin.prototype.addTab = function(){
	var args = this.addTab.arguments;
	this.tabs[args[0]] = new Object();
	this.tabs[args[0]].opened = args[1];
	this.tabs[args[0]].closed = args[2];
	this.tabs[args[0]].link = args[3];
}

defWin.prototype.setTab = function(){
	var args = this.setTab.arguments;
	
	var element = document.getElementById(this.currentTab);
	element.style.backgroundImage = 'url(' + this.tabs[this.currentTab].closed + ')';
	element.className = 'navTabOff';
	
	var element = document.getElementById(args[0]);
	element.style.backgroundImage = 'url(' + this.tabs[args[0]].opened + ')';
	element.className = 'navTabOn';

	this.target.location.href = this.tabs[args[0]].link;	
	this.currentTab = args[0];
}

defWin.prototype.display = function(){
	if(this.display.arguments.length == 1){
		if(!this.display.arguments[0] && this.isOpen()){
			this.force("close");
		} else if(this.display.arguments[0] && !this.isOpen()){
			this.force("open");
		}
	} else {
		if(this.isOpen()){
			this.force("close");
		} else {
			this.force("open");
		}
	}
	
	if(this.isOpen()){
		swapImage(this.resizer[0], this.resizer[1], this.resizer[2]);
	} else {
		swapImage(this.resizer[0], this.resizer[1], this.resizer[3]);
	}
}

defWin.prototype.isOpen = function(){
	var dimString = (this.getDimensions() == this.closed)?false:true;
	return dimString;
}

defWin.prototype.force = function(){
	if(this.force.arguments[0] == "open"){
		this.setDimensions(this.opened);
	} else if (this.force.arguments[0] == "close"){
		this.opened = this.getDimensions();
		this.setDimensions(this.closed);
	}
}

defWin.prototype.setDimensions = function(){
	if(this.layoutType == "rows"){
		this.frm.rows = this.setDimensions.arguments[0];
	} else {
		this.frm.cols = this.setDimensions.arguments[0];
	}
}

defWin.prototype.getDimensions = function(){
	if(this.layoutType == "rows"){
		return (this.frm.rows);
	} else {
		return (this.frm.cols);
	}
}