/*
Script: Hash.js
	Contains the class Hash.

License:
	MIT-style license.
*/

/*
Class: Hash
	It wraps an object that it uses internally as a map. The user must use set(), get(), and remove() to add/change, retrieve and remove values, it must not access the internal object directly. null/undefined values are allowed.

Note:
	Each hash instance has the length property.

Arguments:
	obj - an object to convert into a Hash instance.

Example:
	(start code)
	var hash = new Hash({a: 'hi', b: 'world', c: 'howdy'});
	hash.remove('b'); // b is removed.
	hash.set('c', 'hello');
	hash.get('c'); // returns 'hello'
	hash.length // returns 2 (a and c)
	(end)
*/

var Hash = new Class({

	length: 0,

	init: function(object){
		this.obj = object || {};
		this.setLength();
	},

	/*
	Property: get
		Retrieves a value from the hash.

	Arguments:
		key - The key

	Returns:
		The value
	*/

	get: function(key){
		return (this.hasKey(key)) ? this.obj[key] : null;
	},

	/*
	Property: hasKey
		Check the presence of a specified key-value pair in the hash.

	Arguments:
		key - The key

	Returns:
		True if the Hash contains a value for the specified key, otherwise false
	*/

	hasKey: function(key){
		return (key in this.obj);
	},

	/*
	Property: set
		Adds a key-value pair to the hash or replaces a previous value associated with the key.

	Arguments:
		key - The key
		value - The value
	*/

	set: function(key, value){
		if (!this.hasKey(key)) this.length++;
		this.obj[key] = value;
		return this;
	},

	setLength: function(){
		this.length = 0;
		for (var p in this.obj) this.length++;
		return this;
	},

	/*
	Property: remove
		Removes a key-value pair from the hash.

	Arguments:
		key - The key
	*/

	remove: function(key){
		if (this.hasKey(key)){
			delete this.obj[key];
			this.length--;
		}
		return this;
	},

	/*
	Property: each
		Calls a function for each key-value pair. The first argument passed to the function will be the value, the second one will be the key, like $each.

	Arguments:
		fn - The function to call for each key-value pair
		bind - Optional, the object that will be referred to as "this" in the function
	*/

	each: function(fn, bind){
		$each(this.obj, fn, bind);
	},

	/*
	Property: extend
		Extends the current hash with an object containing key-value pairs. Values for duplicate keys will be replaced by the new ones.

	Arguments:
		obj - An object containing key-value pairs
	*/

	extend: function(obj){
		$extend(this.obj, obj);
		return this.setLength();
	},

	/*
	Property: merge
		Merges the current hash with multiple objects.
	*/

	merge: function(){
		this.obj = $merge.apply(null, [this.obj].extend(arguments));
		return this.setLength();
	},

	/*
	Property: empty
		Empties all hash values properties and values.
	*/

	empty: function(){
		this.obj = {};
		this.length = 0;
		return this;
	},

	/*
	Property: keys
		Returns an array containing all the keys, in the same order as the values returned by <Hash.values>.

	Returns:
		An array containing all the keys of the hash
	*/

	keys: function(){
		var keys = [];
		for (var property in this.obj) keys.push(property);
		return keys;
	},

	/*
	Property: values
		Returns an array containing all the values, in the same order as the keys returned by <Hash.keys>.

	Returns:
		An array containing all the values of the hash
	*/

	values: function(){
		var values = [];
		for (var property in this.obj) values.push(this.obj[property]);
		return values;
	}

});

/* Section: Utility Functions */

/*
Function: $H
	Shortcut to create a Hash from an Object.
*/

function $H(obj){
	return new Hash(obj);
};

/*
Script: Hash.Cookie.js
	Stores and loads an Hash as a cookie using Json format.
*/

/*
Class: Hash.Cookie
	Inherits all the methods from <Hash>, additional methods are save and load.
	Hash json string has a limit of 4kb (4096byte), so be careful with your Hash size.
	Creating a new instance automatically loads the data from the Cookie into the Hash.
	If the Hash is emptied, the cookie is also removed.

Arguments:
	name - the key (name) for the cookie
	options - options are identical to <Cookie> and are simply passed along to it.
		In addition, it has the autoSave option, to save the cookie at every operation. defaults to true.

Example:
	(start code)
	var fruits = new Hash.Cookie('myCookieName', {duration: 3600});
	fruits.extend({
		'lemon': 'yellow',
		'apple': 'red'
	});
	fruits.set('melon', 'green');
	fruits.get('lemon'); // yellow

	// ... on another page ... values load automatically

	var fruits = new Hash.Cookie('myCookieName', {duration: 365});
	fruits.get('melon'); // green

	fruits.erase(); // delete cookie
	(end)
*/

Hash.Cookie = Hash.extend({

	init: function(name, options){
		this.name = name;
		this.options = $extend({'autoSave': true}, options || {});
		this.load();
	},

	/*
	Property: save
		Saves the Hash to the cookie. If the hash is empty, removes the cookie.

	Returns:
		Returns false when the JSON string cookie is too long (4kb), otherwise true.

	Example:
		(start code)
		var login = new Hash.Cookie('userstatus', {autoSave: false});

		login.extend({
			'username': 'John',
			'credentials': [4, 7, 9]
		});
		login.set('last_message', 'User logged in!');

		login.save(); // finally save the Hash
		(end)
	*/

	save: function(){
		if (this.length == 0){
			Cookie.remove(this.name, this.options);
			return true;
		}
		var str = Json.toString(this.obj);
		if (str.length > 4096) return false; //cookie would be truncated!
		Cookie.set(this.name, str, this.options);
		return true;
	},
	
	/*
	Property: load
		Loads the cookie and assigns it to the Hash.
	*/

	load: function(){
		this.obj = Json.evaluate(Cookie.get(this.name), true) || {};
		this.setLength();
	}

});

Hash.Cookie.Methods = {};
['extend', 'set', 'merge', 'empty', 'remove'].each(function(method){
	Hash.Cookie.Methods[method] = function(){
		Hash.prototype[method].apply(this, arguments);
		if (this.options.autoSave) this.save();
		return this;
	};
});
Hash.Cookie.implement(Hash.Cookie.Methods);