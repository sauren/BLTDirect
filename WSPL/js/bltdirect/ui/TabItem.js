using('bltdirect.ui.Tab');

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.TabItem = new Class({
	_className: 'Tabs',
	_classOwner: 'bltdirect.ui.TabItem',
	_items: null,
	
	init: function() {
		this._items = new Array();
	},
	
	addItem: function(item) {
		this._items.push(item);
	},
	
	showItems: function() {
		var element = null;
		
		for(var i=0; i<this._items.length; i++) {
			element = $(this._items[i]);
			
			if(element) {
				element.style.display = '';
			}
		}
	},
	
	hideItems: function() {
		var element = null;
		
		for(var i=0; i<this._items.length; i++) {
			element = $(this._items[i]);
			
			if(element) {
				element.style.display = 'none';
			}
		}
	}
});