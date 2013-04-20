var guides = function() {

	this._sid        = 'guides';
	this._cache      = true;

	this.duration	 = 2000;
	this.interval    = null;

	this.currentPhoto = -1;

	this._ready      = function( fromCache ) {

		if(typeof(fromCache)=='undefined') {
			var fromCache = false;
		}

		var _self = window.kuva.currentController;

		_self.layoutPage();

		_self.loadPage( fromCache );

	};

	this._destroy = function() {
		this.clearInterval();
		$('#guides-core #photo-container').empty();
	};

	this._hashChange = function( hash ) {

	};

	this.layoutPage = function() {

		var _self = window.kuva.currentController;

		var slideWidth = window.kuva.photoSet.width; //Math.round(_self.photoRatio * window.kuva.window.height);
		var slideHeight= window.kuva.photoSet.height; //window.kuva.window.height;

		var slideCSS   = ' #photo-container, #guide-selection { width:'+slideWidth+'px; height:'+slideHeight+'px; } ';
		var slideCSS2   = ' #photo-container, #guide-selection { left:'+( (window.kuva.window.width-slideWidth)/2 )+ 'px; } ';
		var loadingCSS = ' .loading { left:'+((slideWidth/2)-64)+'px; top:'+((slideHeight/2)-64)+'px; } ';

		$('head').append('<style id="guides-style">' + slideCSS + slideCSS2 + loadingCSS + '</style>');

	};

	this.loadPage = function( fromCache ) {

		var _self = window.kuva.currentController;

		_self.changePhoto( 'next' );
		_self.setInterval( );

		if(!fromCache) {
			$.ajax({
				url:'/api/json/guides/current',
				type:'GET',
				dataType: 'json',
				headers: {
					'AJAX': true,
					'ETAG': $('body').data('token')
				},
				success: function(guides) {

					var totalGuides = guides.length;

					var rows = Math.floor( (window.kuva.window.height-108)/104 );
					var cols = Math.ceil(totalGuides/rows);

					var guideIndex = 0;

					for(var colIndex=0; colIndex<cols; colIndex++) {

						var g = [];

						for(var rowIndex=0; rowIndex<rows; rowIndex++) {
							if(typeof(guides[guideIndex])!='undefined') {
								g.push( guides[ guideIndex ] );
								guideIndex++;
							}
						}

						$('#guides-core #guide-selection').jqoteapp('#tmpl-guides-buttons', { guides:g });

					}

				}
			});
		}

	};

	this.changePhoto = function( nextPrev, getNumber ) {
		var _self = window.kuva.currentController;

		var newPhoto = 0;

		if(!isNaN(nextPrev)) {
			newPhoto = nextPrev;
		}
		else {
			if(nextPrev=='next') {
				if(_self.currentPhoto < window.kuva.photoSet.total-1) {
					newPhoto = parseInt(_self.currentPhoto)+1;
				}
				else {
					newPhoto = 0;
				}
			}
			else {
				if(_self.currentPhoto > 0) {
					newPhoto = parseInt(_self.currentPhoto)-1;
				}
				else {
					newPhoto = parseInt(window.kuva.photoSet.total)-1;
				}
			}
		}

		if(typeof(getNumber)!='undefined') {
			return newPhoto;
		}

		$('#photo'+newPhoto).appendTo('#photo-container').fadeIn(500);
		$('#photo'+_self.currentPhoto).delay(500).fadeOut(500, function() { $(this).appendTo('#photo-repository'); });

		_self.currentPhoto = newPhoto;

	};


	this.setInterval = function() {
		var _self = window.kuva.currentController;
		_self.interval = setInterval(function(){

			window.kuva.currentController.changePhoto('next');

		}, _self.duration);
	};

	this.clearInterval = function() {
		window.kuva.currentController.interval = window.clearInterval(window.kuva.currentController.interval);
		window.kuva.currentController.interval = null;
	};

};

window.kuva.addController( guides );