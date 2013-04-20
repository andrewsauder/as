var loading = function() {

	this._sid        = 'loading';
	this._cache      = false;

	this.photoSet = {

		ratio:1.5,
		height:null,
		width:null,

		urls:[],
		total:0

	};

	this._ready      = function( fromCache ) {

		window.kuva.reload = false;

		if(typeof(fromCache)=='undefined') {
			var fromCache = false;
		}

		var _self = window.kuva.currentController;

		_self.loadPage( fromCache );

	};

	this._destroy = function() {
		$('.photo').off('load');
		window.kuva.photoSet = this.photoSet;
	};

	this._hashChange = function( hash ) {

	};

	this.loadPage = function( fromCache ) {

		$('#photo-repository').empty();

		var _self = window.kuva.currentController;

		_self.photoSet.width  = Math.round(_self.photoSet.ratio * window.kuva.window.height);
		_self.photoSet.height = window.kuva.window.height;

		$.ajax({
				url:'/api/json/photos/current/image', //optionally could be current/base64 for datauris
				type:'GET',
				dataType: 'json',
				headers: {
					'AJAX': true,
					'ETAG': $('body').data('token')
				},
				success: function(rawPhotoURLs) {

					for(var index=0; index<rawPhotoURLs.length; index++) {
						_self.photoSet.urls.push( rawPhotoURLs[index]+'/'+_self.photoSet.width+'/'+_self.photoSet.height );
					}

					_self.photoSet.total = _self.photoSet.urls.length;

					if(_self.photoSet.total>0) {
						_self.loadImages();
					}

				}
		});

	};

	this.loadImages = function() {

		var _self = this;

		$('.loading-info').find('.total').text(_self.photoSet.total);

		for(var i=0; i<_self.photoSet.total; i++) {
			$('#photo-repository').append('<img data-src="'+_self.photoSet.urls[i]+'" id="photo'+i+'" class="photo" data-index="'+i+'" width="'+_self.photoSet.width+'" height="'+_self.photoSet.height+'" />');
			$('#photo'+i).on('load', function() {
				_self.loadedImage( $(this) );
			}).error(function() {
				_self.loadedImage( $(this), true );
			});
		}

		$('.loading-info').find('.cur').text('1');
		$('#photo0').attr('src', $('#photo0').data('src') );

	};

	this.loadedImage = function( $el, error ) {
		var next = parseInt($el.data('index'))+1;

		$el.removeData('src').removeAttr('data-src');

		if( $('#photo'+next).length>0 ) {

			$('.loading-info').find('.cur').text(next+1);
			$('#photo'+next).attr('src', $('#photo'+next).data('src') );

		}
		else {

			window.location.hash = '#/guides/';

		}

		if(typeof(error)!='undefined' && error) {
			$el.remove();
		}
	};


};

window.kuva.addController( loading );