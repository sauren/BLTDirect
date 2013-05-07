var evance = evance || {};

evance.url = function(url){
	var str = evance.root;
	if(str.substr(-1) == '/'){
		str = str.substr(0, (str.length-1));
	}
	if(url.substr(0, 1) != '/'){
		url = '/' + url;
	}
	return (str+url);
}

/*
	Prototype style object inheritence
*/
if (typeof Object.beget !== 'function') {
    Object.beget = function (o) {
        function F() {}
        F.prototype = o;
        return new F();
    };
}

/*
	Supplant
	usage: var myStr = 'Hello my name is {name}';
	myStr.supplant({name: 'Geoff'});
*/
String.prototype.supplant = function (o) {
    return this.replace(/{([^{}]*)}/g,
        function (a, b) {
            var r = o[b];
            return typeof r === 'string' || typeof r === 'number' ? r : a;
        }
    );
};

Function.prototype.method = function(fname, f){
    this.prototype[fname] = f;
    return this;
};

// Fix Javascript's broken mod.
Number.prototype.mod = function(n) {
	return ((this%n)+n)%n;
};

/*
	Object comparison field by field
*/
objectsEqual = function(a,x) {
	for (p in a) {
		if (typeof(x[p])=='undefined') {return false;}
	}
	
	for (p in a) {
		if (a[p]) {
			switch(typeof(a[p])) {
				case 'object':
					if (!a[p].equals(x[p])) { return false; }
					break;
				case 'function':
					if (typeof(x[p])=='undefined'
						|| (p != 'equals' && a[p].toString() != x[p].toString())
					)
					{
						return false; 
					}
					break;
				default:
					if (a[p] != x[p]) { return false; }
			}
		} else {
			if (x[p]) {	return false; }
		}
	}
 
	for (p in x) {
		if (typeof(a[p])=='undefined') { return false; }
	}
	
	return true;
}

evance.cookie = {
	save: function(name, value, days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	},
	
	read: function (name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	},
	
	erase: function(name) {
		createCookie(name,"",-1);
	}
};

/*
	evance.events
	-----------------
	returns an object with bind, trigger and unbind event capabilities.
*/
evance.events = function() {
    var events = {};
    
    return {
        bind: function(name, fn) {
            events[name] = events[name] || [];
            events[name].push(fn);
        },
        trigger: function(name, obj) {
            if (!events[name]) return;
            jQuery.each(events[name], function(i, fn) { fn(obj || {}); });
        },
        unbind: function(name, fn) {
            if (!events[name]) return;
            events[name] = jQuery.grep(events[name], function(n) { return n != fn; });
        }
    };
};

function stristr (haystack, needle, bool) {
    var pos = 0;
    haystack += '';
    pos = haystack.toLowerCase().indexOf((needle + '').toLowerCase());    if (pos == -1) {
        return false;
    } else {
        if (bool) {
            return haystack.substr(0, pos);
        } else {
            return haystack.slice(pos);
        }
    }
}