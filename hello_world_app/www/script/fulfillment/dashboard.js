var dashboard = function() {

	this._sid        = 'dashboard';
	this._cache      = false;

	this._ready      = function( fromCache ) {

		if(typeof(fromCache)=='undefined') {
			var fromCache = false;
		}

		var _self = window.kuva.currentController;

		_self.layoutPage();

		_self.loadPage( fromCache );

	};

	this._destroy = function() {

	};

	this._hashChange = function( hash ) {

	};

	this.layoutPage = function() {

	};

	this.loadPage = function( fromCache ) {

	};

};

window.kuva.addController( dashboard );