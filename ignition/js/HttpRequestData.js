/**
 * Class to provide the ability to store and extract request data.
 * Allows data extraction from all visible fields of HTML forms into
 * variable-value pairs, and the addition and removal of unwanted
 * data strings.
 *
 * @version 1.2
 */
function HttpRequestData() {
	this.data = new Array();
	this.autoClear = false;

	/**
	 * Sets a single variable-value string into the data array.
	 * If the variable name already exists in the array then its
	 * value will be replaced with the specified input.
	 *
	 * @param variable The unique identifier for the variable-pair.
	 * @param value The value of the variable to be set.
	 */
	this.setData = function(variable, value) {
		var pair = null;

		for(var i = 0; i < this.data.length; i++) {
			pair = this.data[i].split('=');

			if(pair[0] == variable) {
				this.data[i] = variable + '=' + encodeURI(value);
				return true;
			}
		}

		this.data.push(variable + '=' + value);
	}

	/**
	 * Removes a single entry within the data array when found.
	 *
	 * @param variable The unique identifier for the variable-pair.
	 * @returns True if the variable was removed from the data array.
	 */
	this.removeData = function(variable) {
		var pair = null;

		for(var i = 0; i < this.data.length; i++) {
			pair = this.data[i].split('=');

			if(pair[0] == variable) {
				this.data.splice(i, 1)
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the value of a specified variable when found to be
	 * in the data array or false if the variable cannot be found.
	 *
	 * @returns The value of a variable stored when found.
	 */
	this.getData = function(variable) {
		var pair = null;

		for(var i = 0; i < this.data.length; i++) {
			pair = this.data[i].split('=');

			if(pair[0] == variable) {
				return pair[1];
			}
		}

		return false;
	}

	/**
	 * Clears the data array of all variable-value pairs.
	 */
	this.clearData = function() {
		this.data = new Array();
	}

	/**
	 * Defines whether the returning of request data should clear
	 * all stored values within the data array.
	 *
	 * @param autoClear Purges data array if true when data requested.
	 */
	this.setAutoClear = function(autoClear) {
		this.autoClear = autoClear;
	}

	/**
	 * Constructs and returns a single query string style string
	 * of variable-value pairs of the contents of the data array
	 * ready for XmlHttpRequests.
	 *
	 * @returns Query string of variable-value pairs.
	 */
	this.getHttpRequestData = function() {
		var dataString = '';

		for(var i = 0; i < this.data.length; i++) {
			dataString += '&' + this.data[i];
		}

		if(this.autoClear) {
			this.data = new Array();
		}

		return dataString.substr(1, dataString.length);
	}

	/**
	 * Stores data from all input elements of an HTML web form
	 * into an array ready for returning as a query string. Encodes
	 * variable values to handle non-ASCII characters.
	 *
	 * @param formReference String reference to a form DOM-element.
	 */
	this.getFormData = function(formReference) {
		var form = document.getElementById(formReference);
		var formData = '';
		var formElement = null;
		var formGroups = new Array();
		var groupArray = null;
		var groupFound = false;
		var listOption = null;
		var listString = '';

		for(var i = 0; i < form.elements.length; i++) {
			formElement = form.elements[i];

			switch(formElement.type) {
				case 'text':
				case 'hidden':
				case 'password':
				case 'textarea':
				case 'select-one':
					formData += formElement.name + '=' + encodeURI(formElement.value) + '&';
	        		break;
				case 'select-multiple':
					for(var j = 0; j < formElement.options.length; j++) {
					   	var listOption = formElement.options[j];

						if(listOption.selected) {
							listString += encodeURI(listOption.value) + ',';
						}
					}

					formData += formElement.name + '=' + listString.substr(0, listString.length - 1) + '&';
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
						formData += formElement.name + '=' + encodeURI(formElement.value) + '&';
			        }
			        break;
			}
		}

		for(var i = 0; i < formGroups.length; i++) {
			formData += formGroups[i][0] + '=' + formGroups[i][1].substr(0, formGroups[i][1].length - 1) + '&';
		}

		formData = formData.substr(0, formData.length - 1);
		formData = formData.split('&');

		for(var i = 0; i < formData.length; i++) {
			this.data.push(formData[i]);
		}

		form = null;
	}
}