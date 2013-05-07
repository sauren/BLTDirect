if(!$defined(evance.net)){evance.net = {}};

evance.net.RequestData = new Class({
	_className: 'RequestData',
	_classOwner: 'evance.net.RequestData',
	_data: new Array(),
	_autoClear: false,

	addItem: function(variable, value) {
		var pair = null;

		for(var i = 0; i < this._data.length; i++) {
			pair = this._data[i].split('=');

			if(pair[0] == variable) {
				this._data[i] = variable + '=' + encodeURI(value);
				return true;
			}
		}

		this._data.push(variable + '=' + value);
		return false;
	},

	getItem: function(variable) {
		var pair = null;

		for(var i = 0; i < this._data.length; i++) {
			pair = this._data[i].split('=');

			if(pair[0] == variable) {
				return pair[1];
			}
		}

		return false;
	},

	removeItem: function(variable) {
		var pair = null;

		for(var i = 0; i < this._data.length; i++) {
			pair = this._data[i].split('=');

			if(pair[0] == variable) {
				this._data.splice(i, 1)
				return true;
			}
		}

		return false;
	},

	removeAll: function() {
		this._data = new Array();
	},

	setAutoClear: function(autoClear) {
		this.autoClear = autoClear;
	},

	serialise: function() {
		var dataString = '';

		for(var i = 0; i < this._data.length; i++) {
			dataString += '&' + this._data[i];
		}

		if(this.autoClear) {
			this._data = new Array();
		}

		return dataString.substr(1, dataString.length);
	},

	loadFromForm: function(formReference) {
		var form = $(formReference);
		var formElement = null;
		var formGroups = new Array();
		var groupArray = null;
		var groupFound = false;
		var listOption = null;
		var listString = '';

		if($defined(form)) {
			for(var i = 0; i < form.elements.length; i++) {
				formElement = form.elements[i];

				switch(formElement.type) {
					case 'text':
					case 'hidden':
					case 'password':
					case 'textarea':
					case 'select-one':
						this.addItem(formElement.name, encodeURI(formElement.value));
		        		break;
					case 'select-multiple':
						for(var j = 0; j < formElement.options.length; j++) {
						   	var listOption = formElement.options[j];

							if(listOption.selected) {
								listString += encodeURI(listOption.value) + ',';
							}
						}

						this.addItem(formElement.name, listString.substr(0, listString.length - 1));
						break;
					case 'checkbox':
						if(formElement.checked) {
							groupFound = false;

							for(j = 0; j < formGroups.length; j++) {
								if(formGroups[j][0] == formElement.name) {
									formGroups[j][1] += encodeURI(formElement.value) + ',';

									groupFound = true;
									break;
								}
							}

							if(!groupFound) {
								groupArray = new Array(2);
								groupArray[0] = formElement.name;
								groupArray[1] = encodeURI(formElement.value) + ',';

								formGroups[formGroups.length] = groupArray;
							}
		 				}
						break;
					case 'radio':
				        if(formElement.checked) {
							this.addItem(formElement.name, encodeURI(formElement.value));
				        }
				        break;
				}
			}

			for(var i = 0; i < formGroups.length; i++) {
				this.addItem(formGroups[i][0], formGroups[i][1].substr(0, formGroups[i][1].length - 1));
			}
		}
	}
});