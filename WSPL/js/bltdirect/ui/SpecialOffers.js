if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.SpecialOffers = bltdirect.ui.ItemBlend.extend({
	_className: 'SpecialOffers',
	_classOwner: 'bltdirect.ui.SpecialOffers',
	
	init: function() {
		this.parent(arguments);
		
		this.limit = 6;
	},
	
	onHttpRequestResponse: function(data) {
		this.parent(data);
		
		var div = null;
		
		if(this._itemContainer) {
			this._itemContainer.innerHTML = '';
			
			for(var i=0; i<this._items.length; i++) {
				if(this._items[i].length == 5) {
					div = document.createElement('div');
					div.id = this.id + '-' + i;
					div.style.display = 'none';
					div.className = '';
					div.innerHTML += '<p class="offerWhite"><span class="offerPercentage">' + this._items[i][3] + '%</span><span class="offerOff">ff</span></p>';
					div.innerHTML += '<p><a href="./product.php?pid=' + this._items[i][0] + '">' + this._items[i][1] + '</a></p>';
					div.innerHTML += '<p class="offerPrice">Only <span class="offerWhite">&pound;' + this._items[i][2] + '</span></p>';
					
					this._itemContainer.appendChild(div);
				}
			}
			
			if(this._imageContainer) {
				this._imageContainer.innerHTML = '';
				
				for(var i=0; i<this._items.length; i++) {
					if(this._items[i].length == 5) {
						div = document.createElement('div');
						div.id = this.id + '-' + i + '-image';
						div.style.display = 'none';
						div.className = '';

						if(this._items[i][4].length > 1) {
							div.innerHTML += '<a href="./product.php?pid=' + this._items[i][0] + '" title="' + this._items[i][1] + '"><img src="' + this._items[i][4] + '" alt="' + this._items[i][1] + '" /></a>';
						}
						
						this._imageContainer.appendChild(div);
					}
				}
			}
			
			if(this._navContainer && (this._navImgOn.length > 0) && (this._navImgOff.length > 0)) {
				this._navContainer.innerHTML = '';
				
				for(var i=0; i<this._items.length; i++) {
					if(this._items[i].length == 5) {
						img = new bltdirect.ui.SpecialOfferButton(this.id + '-' + i + '-nav');
						img.setOwner(this);
						img.draw(i);
					}
				}
			}
			
			this.start();
		}
	}
});