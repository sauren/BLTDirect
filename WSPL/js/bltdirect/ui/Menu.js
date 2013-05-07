using('bltdirect.ui.MenuClass');
using('bltdirect.ui.MenuItem');

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.Menu = new Class({
	_className: 'Menu',
	_classOwner: 'bltdirect.ui.Menu',
	classes: null,
	instance: null,
	items: null,
	id: 0,
	interval: null,
	timeout: 500,
	isOpen: false,
	operation: 'onmouseover',
	
	init: function() {
		this.instance = arguments[0];
		
		this.classes = new Array();
		this.items = new Array();
	},
		
	load: function() {		
		this.build();
	},
	
	addClass: function(id, name, direction){
		this.classes[this.classes.length] = new bltdirect.ui.MenuClass(id, name, direction);
	},
	
	getClass: function(id){
		for(var i=0; i < this.classes.length; i++){
			if(id == this.classes[i].id) return this.classes[i];
		}
	},
	
	add: function(id, parent, label, url, icon, classId){
		if(parent == null){
			this.items[this.items.length] = new bltdirect.ui.MenuItem(id, this, label, url, icon, classId, this.instance);
			return true;
		} else {
			for(var i=0; i < this.items.length; i++){
				var node = this.items[i];
				if(node.add(id, parent, label, url, icon, classId, this.instance)){
					node.hasChildren = true;
					return true;
				}
			}
		}
		return false;
	},
	
	onRelease: function(id){
		this.isOpen = !this.isOpen;
		if(this.isOpen){
			this.open(id);
		} else {
			this.close();
		}
	},
	
	onRightClick: function(id){
		this.isOpen = true;
		this.open(id);
	},
	
	onRollOver: function(id){
		clearTimeout(this.interval);
		if(this.isOpen || (this.operation == 'onmouseover' && !this.isOpen)) this.open(id);
	},
	
	onRollOut: function(id){
		var ref = this;
		this.interval = setTimeout(function(){ref.close();}, this.timeout);
	},
	
	open: function(id){
		for(var i=0; i < this.items.length; i++){
			if(this.items[i].open(id)) return;
		}
	},
	
	close: function(){
		for(var i=0; i < this.items.length; i++){
			this.items[i].close();
		}
		this.isOpen = false;
	},
	
	closeChildrenExcept: function(id){
		for(var i=0; i < this.items.length; i++){
			if(this.items[i].id != id) this.items[i].close();
		}
	},
	
	build: function(){
		for(var i=0; i < this.items.length; i++){
			this.items[i].build();
		}
	}
});