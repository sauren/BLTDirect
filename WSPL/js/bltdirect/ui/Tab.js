if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.Tab = new Class({
	_className: 'Tab',
	_classOwner: 'bltdirect.ui.Tab',
	_tabs: null,
	_tabItems: null,
	
	init: function() {
		this._tabs = new Array();
		this._tabItems = new Array();
	},
	
	addTab: function(tab) {
		this._tabs.push(tab);
		this._tabItems.push(new bltdirect.ui.TabItem);
	},
	
	addTabItem: function(tab, item) { 
		for(var i=0; i<this._tabs.length; i++) {
			if(this._tabs[i] == tab) {
				this._tabItems[i].addItem(item);
			
				return true;
			}
		}
		
		return false;
	},
	
	showTab: function(tab) {
		for(var i=0; i<this._tabs.length; i++) {
			if(this._tabs[i] == tab) {
				this._tabItems[i].showItems();
			} else {
				this._tabItems[i].hideItems();
			}
		}
	}
});