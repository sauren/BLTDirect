if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.MenuClass = new Class({
	_className: 'MenuClass',
	_classOwner: 'bltdirect.ui.MenuClass',
	id: 0,
	name: null,
	direction: null,
	
	init: function() {
		this.id = arguments[0];
		this.name = arguments[1];
		this.direction = arguments[2];
	}
});