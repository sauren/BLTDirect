using('evance.net.Request');
using('evance.net.RequestData');

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.SearchBox = evance.core.UIObject.extend({
	_className: 'SearchBox',
	_classOwner: 'bltdirect.ui.SearchBox',
	_requestURL: '',
	_hideTimeout: null,
	request: null,
	requestData: null,
	results: null,
	items: null,
	itemsReplacements: null,
	itemsOverrides: null,

	init: function() {
		this.parent(arguments[0]);

		this.request = new evance.net.Request();
		this.request.setDelay(250);
		this.request.setCaching(true);
		this.request.addListener(this);

		this.requestData = new evance.net.RequestData();
		this.requestData.addItem('limit', '15');

		this.items = new Array();
		this.itemsReplacements = new Array();
		this.itemsOverrides = new Array();

		this.results = new evance.core.UIObject();
		this.results.addListener(this);
	},

	load: function() {
		this.parent();

		var element = document.createElement('div');
		element.id = 'SearchResults';
		element.style.top = (this.element.getPosition().y + this.element.offsetHeight) + 'px';
		element.style.left = this.element.getPosition().x + 'px';
		element.style.display = 'none';
		element.style.width = this.element.offsetWidth - 12;
		element.className = 'SearchResults';

		this.results.id = element.id;
		this.results.load();

		document.body.appendChild(element);
	},

	setRequestURL: function(url) {
		this._requestURL = url;
	},

	setTextField: function(textField) {
		this.id = textField;
	},

	closeWindow: function() {
		this.hideItems();
	},

	addItem: function(item) {
		this.items.push(item);
	},

	addItemReplacement: function(replacement) {
		this.itemsReplacements.push(replacement);
	},

	addItemOverride: function(override) {
		this.itemsOverrides.push(override);
	},

	prepareMatch: function(val){
		val = val.replace(/\\/g, '\\\\');
		val = val.replace(/\//g, '\\\/');

		return val;
	},

	drawItems: function() {
		var self = this;
		var items = null;
		var pos = 0;
		var contents = '';
		var values = this.element.value.replace(/-/g, '').replace(/\./g, '').replace(/\//g, '').replace(/\(/g, '').replace(/\)/g, '').split(/\s/g);
		var anchor = null;
		var div = null;
		var productTitle = '';
		var productSku = '';
		var parsedInteger = 0;
		var replacement = '';
		var parts = null;

		if(this.itemsReplacements.length > 0) {
			contents += '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
			contents += '<tr><td class="ResultReplacements">';
			contents += '<strong>Did you mean?</strong> ';

			for(var i=0; i<this.itemsReplacements.length; i++) {
				contents += '<a href="javascript:searchReplace(\'' + this.itemsReplacements[i] + '\');">' + this.itemsReplacements[i] + '</a>';

				if((i+1) < this.itemsReplacements.length) {
					contents += ', ';
				}
			}
			
			contents += '</td></tr>';
			contents += '</table>';
		}

		if(this.itemsOverrides.length > 0) {
			contents += '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
			contents += '<tr><td class="ResultOverrideHeader">';
			contents += '<strong>Have you considered?</strong> ';
			contents += '</td></tr>';

			for(var i=0; i<this.itemsOverrides.length; i++) {
				parts = this.itemsOverrides[i].split('{br}');
				
				if(parts[0] == 'product') {
					contents += '<tr><td class="ResultOverride ResultOverrideProduct">Product: <a href="./product.php?pid=' + parts[1] + '">' + parts[2] + '</a></td></tr>';

				} else if(parts[0] == 'category') {
					contents += '<tr><td class="ResultOverride ResultOverrideCategory">Category: <a href="./products.php?cat=' + parts[1] + '">' + parts[2] + '</a></td></tr>';
				}
			}
			
			contents += '</table>';
		}
		
		if(this.items.length > 0) {
			contents += '<table width="100%" border="0" cellspacing="0" cellpadding="0">';

			parsedInteger = parseInt(this.requestData.getItem('criteria'));

			if(!isNaN(parsedInteger)) {
				for(var i=0; i<this.items.length; i++) {
					if(parsedInteger == this.items[i][1]) {
						items = this.items[i][0].toLowerCase().split(/\s/g);

						for(var j=0; j<values.length; j++) {
							for(var k=0; k<items.length; k++) {
								if(items[k].replace(' ', '').length > 0) {
									items[k] = items[k].replace(eval('/^' + this.prepareMatch(values[j]) + '/gi'), ' <span class="Match">' + values[j] + '</span>');
								}
							}
						}

						productTitle = items.join(' ');

						if(this.items[i][2]) {
							items = this.items[i][2].toLowerCase().split(/\s/g);

							for(var j=0; j<values.length; j++) {
								for(var k=0; k<items.length; k++) {
									if(items[k].replace(' ', '').length > 0) {
										items[k] = items[k].replace(eval('/' + this.prepareMatch(values[j]) + '/gi'), '<span class="Match">' + values[j] + '</span>');
									}
								}
							}

							productSku = items.join(' ');
						} else {
							productSku = '';
						}

						contents += '<tr class="ResultDirectMatch"><td class="ResultLeft"><a href="./product.php?pid=' + this.items[i][1] + '">' + productTitle + '<br /><span class="ResultLeftSub">' + productSku + '</span></a></td><td class="ResultRight"><a href="./product.php?pid=' + this.items[i][1] + '">' + this.items[i][1] + '</a></td></tr>';

						this.items.splice(i, 1);
						
						break;
					}
				}
			}

			for(var i=0; i<this.items.length; i++) {
				items = this.items[i][0].toLowerCase().split(/\s/g);

				for(var j=0; j<values.length; j++) {
					for(var k=0; k<items.length; k++) {
						if(items[k].replace(' ', '').length > 0) {
							items[k] = items[k].replace(eval('/^' + this.prepareMatch(values[j]) + '/gi'), ' <span class="Match">' + values[j] + '</span>');
						}
					}
				}

				productTitle = items.join(' ');

				if(this.items[i][2]) {
					items = this.items[i][2].toLowerCase().split(/\s/g);

					for(var j=0; j<values.length; j++) {
						for(var k=0; k<items.length; k++) {
							if(items[k].replace(' ', '').length > 0) {
								items[k] = items[k].replace(eval('/' + this.prepareMatch(values[j]) + '/gi'), '<span class="Match">' + values[j] + '</span>');
							}
						}
					}

					productSku = items.join(' ');
				} else {
					productSku = '';
				}

				contents += '<tr><td class="ResultLeft"><a href="./product.php?pid=' + this.items[i][1] + '">' + productTitle + '<br /><span class="ResultLeftSub">' + productSku + '</span></a></td><td class="ResultRight"><a href="./product.php?pid=' + this.items[i][1] + '">' + this.items[i][1] + '</a></td></tr>';
			}

			contents += '</table>';
		}

		if((this.items.length > 0) || (this.itemsReplacements.length > 0) || (this.itemsOverrides.length > 0)) {
			anchor = document.createElement('a');
			anchor.appendChild(document.createTextNode('close'));
			anchor.href = '#';
			anchor.onclick = function() {
				self.closeWindow();
				return false;
			}

			div = document.createElement('div');
			div.className = 'CloseWindow';
			div.appendChild(anchor);

			this.results.element.innerHTML = contents;
			this.results.element.appendChild(div);
			this.results.element.style.display = '';
		}
	},

	hideItems: function() {
		this.results.element.style.display = 'none';
	},

	showItems: function() {
		if(this.items.length > 0) {
			this.results.element.style.display = '';
			this.results.element.style.top = (this.element.getPosition().y + this.element.offsetHeight) + 'px';
			this.results.element.style.left = this.element.getPosition().x + 'px';
		}
	},

	clearItems: function() {
		this.results.element.innerHTML = '';
		this.results.element.style.display = 'none';

		this.items = new Array();
		this.itemsReplacements = new Array();
		this.itemsOverrides = new Array();
	},

	processSearch: function(e) {
		if(this.element.value.length > 0) {
			this.requestData.addItem('criteria', this.element.value);

			this.request.post(this._requestURL, this.requestData.serialise());
		} else {
			this.clearItems();
		}
	},

	onHttpRequestResponse: function(data) {
		var parts = data.response.split('{br}{br}{br}');
		var items = null;
		
		this.clearItems();
		
		if(parts[0]) {
			items = parts[0].split('{br}{br}');
			
			for(var i=0; i<items.length; i++) {
				if(items[i].length > 0) {
					this.addItem(items[i].split('{br}'));
				}
			}
		}
		
		if(parts[1]) {
			replacement = parts[1].split('{br}{br}');
			
			for(var i=0; i<replacement.length; i++) {
				if(replacement[i].length > 0) {
					this.addItemReplacement(replacement[i]);
				}
			}
		}

		if(parts[2]) {
			override = parts[2].split('{br}{br}');

			for(var i=0; i<override.length; i++) {
				if(override[i].length > 0) {
					this.addItemOverride(override[i]);
				}
			}
		}
			
		this.drawItems();
	},

	onHttpRequestError: function(data) {
		evance.debug(data.status);
	},

	onKeyUp: function(e) {
		this.processSearch();
	},

	onMouseOut: function(e) {
		var self = this;

		if(this._hideTimeout) {
			clearTimeout(this._hideTimeout);
		}

		this._hideTimeout = setTimeout(function() {
			self.hideItems();
		}, 500);
	},

	onMouseOver: function(e) {
		if(this._hideTimeout) {
			clearTimeout(this._hideTimeout);
		}

		this.showItems();
	}
});