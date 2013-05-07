
/*
	JavaScript CSS function
*/
	function jscss(action, obj, c1, c2){
		switch(action){
				case 'swap':
					obj.className = !jscss('check', obj, c1)?obj.className.replace(c2,c1):obj.className.replace(c1,c2);
					break;
				case 'add':
					if(!jscss('check', obj, c1)){ obj.className+=obj.className?' '+c1:c1;}
					break;
				case 'remove':
					var rep=obj.className.match(' '+c1)?' '+c1:c1;
					obj.className=obj.className.replace(rep,'');
					break;
				case 'check':
					return new RegExp('\\b'+c1+'\\b').test(obj.className);
					break;
		}
	}
	
/*
	MM Status Changer
*/
	function MM_displayStatusMsg(msgStr) { //v1.0
	  status=msgStr;
	  document.MM_returnValue = true;
	}