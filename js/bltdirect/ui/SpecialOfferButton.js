if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.SpecialOfferButton = evance.core.UIObject.extend({
	_className: 'SpecialOfferButton',
	_classOwner: 'bltdirect.ui.SpecialOfferButton',
	_owner: null,
	_index: 0,
	
	init: function() {
		this.parent(arguments[0]);
	},

	draw: function(index) {
		this._index = index;
		
		if(this._owner) {
			this.parentElement = $(this._owner._navContainer.id);
			
			if(this.parentElement) {
				this.element = document.createElement('img');
				this.element.id = this.id;
				this.element.src = this._owner._navImgOff;
				this.element.height = 10;
				this.element.width = 10;
				this.element.style.visibility = 'hidden';
				this.element.alt = this._owner._items[index][1];
	
				this.parentElement.appendChild(this.element);
				this.parentElement.appendChild(document.createTextNode('\u00a0'));

				this.load();
	
				return true;
			}
		}
		
		return false;
	},

	setOwner: function(owner) {
		this._owner = owner;
	},
	
	onClick: function(e) {
		this._owner.stop(this._index);
	}
});