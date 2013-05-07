	/* --------------------------------------------------------
		AutoComplete()
		usage: var field = new AutoComplete([string], [[string], ...]);
		must receive at least 2 arguments
		argument 0: is the field id name of the textfield to apply autocomplete to
		argument 1-n: are value arguments for the autocomplete field to compare
		
	-------------------------------------------------------- */
	function AutoComplete(){
		this.args = arguments;
		this.obj;
		this.selected = null;
		this.isInit = false;
		
		this.init = function(){
			this.obj = document.getElementById(this.args[0]);
			this.isInit = true;
		}
					
		this.update = function(){
			if(!this.isInit) this.init();
			if(event.keyCode != 8 && event.keyCode != 16 && event.keyCode != 20 && this.obj.value != ''){
				var reStr = "^" + this.obj.value.toLowerCase();
				var re = new RegExp(reStr);
				for(var i=1; i < this.args.length; i++){
					var matchStr = this.args[i].toLowerCase();
					if(matchStr.match(re)){
						var chars = this.obj.value.length;
						var newChars = this.args[i];
						// Selection Function
						this.obj.value = this.obj.value + newChars.substr(chars, newChars.length);
						iTextSelect(this.obj, chars);
						this.selected = i;
						break;
					} else {
						this.selected = false;
					}
				}
			}
		}
		
		this.keyHandler = function(){
			if(!this.isInit) this.init();
			if(event.keyCode == 13){
				this.obj.value = (this.isSelection())?this.args[this.selected]:this.obj.value;
				event.returnValue = false;
			} else if(event.keyCode == 9){
				this.obj.value = (this.isSelection())?this.args[this.selected]:this.obj.value;
			}
		}
		
		this.isSelection = function(){
			var oRange = document.selection.createRange();
			return (oRange.text == '')?false:true;
		}
	}
	
	/* --------------------------------------------------------
		textAreaCounter([object], [int]);
		limits the number of characters of a html textarea
	 -------------------------------------------------------- */
	function textAreaCounter(field, maxlimit) {
		//var field = document.getElementById(obj);
		var remaining = document.getElementById(field.name + "Info");
		if (field.value.length > maxlimit){
			field.value = field.value.substring(0, maxlimit);
		} else  {
			remaining.value = maxlimit - field.value.length;
		}
	}
	
	/* --------------------------------------------------------
		disableSubmit([object])
		disables any submit button within a form
		normally used for form onSubmit() events
	-------------------------------------------------------- */
	function disableSubmit(formObj){
		for(i=0; i<formObj.length; i++){
			var tempObj = formObj.elements[i];
			if(tempObj.type.toLowerCase() == "submit"){
				tempObj.disabled = true;
			}
		}
	}