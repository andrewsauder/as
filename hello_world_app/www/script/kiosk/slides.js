var slides = function() {

	this._sid        = 'slides';
	this.duration	 = 3;

	this.interval    = null;

	this.guidePhotoSet = {
		urls:[],
		total:0
	};
	this.currentPhoto= -1;

	this._ready      = function() {

		var _self = window.kuva.currentController;

		_self.layoutSlidePage();
		_self.loadSlidePage();

		_self.duration = _self.duration*1000;

		$('#slides-core').on('click.slides', '.add-to-order', function() {

			window.kuva.order.add( $(this).data('productid'), $(this).find('.hidden-order-text').text(), _self.guidePhotoSet.urls[ _self.currentPhoto ] );
			_self.updateOrder();
			_self.exitOnFree();

		});

		$('#slides-core').on('click.slides', '.cancel-order', function() {
			window.kuva.order.cancel();
			window.location.hash = '#/guides';
		});

		$('#slides-core').on('click.slides', '.nextPhoto', function() {
			_self.exitOnFree();
			window.kuva.currentController.clearInterval();
			window.kuva.currentController.updateURL( 'next' );
			window.kuva.currentController.setInterval();
		});

		$('#slides-core').on('click.slides', '.prevPhoto', function() {
			_self.exitOnFree();
			window.kuva.currentController.clearInterval();
			window.kuva.currentController.updateURL( 'prev' );
			window.kuva.currentController.setInterval();
		});

	};

	this._destroy = function() {
		this.interval = window.clearInterval(this.interval);
		$('#slides-core').off('click.slides');
		$('#slides-core .photo').each(function() { $(this).appendTo('#photo-repository'); });
		$.doTimeout('exitOnFree');
	};

	this._hashChange = function( hash ) {

		var _self = window.kuva.currentController;

		_self.changePhoto( hash.R3 );

	};

	this.exitOnFree = function() {
		$.doTimeout('exitOnFree', 120000, function() {
			window.kuva.order.cancel();
			window.location.hash = '#/guides/';
		}, true);
	};

	this.layoutSlidePage = function() {

		var _self = window.kuva.currentController;

		var slideWidth = window.kuva.photoSet.width; //Math.round(_self.photoRatio * window.kuva.window.height);
		var slideHeight= window.kuva.photoSet.height;

		var sidebarTotalWidth = window.kuva.window.width - slideWidth;
		var sidebarPadding    = Math.round(.066 * sidebarTotalWidth);
		var sidebarCSSHeight  = window.kuva.window.height - (sidebarPadding*2);
		var sidebarCSSWidth   = sidebarTotalWidth - (sidebarPadding*2);

		var sidebarCSS = ' #sidebar { width:'+sidebarCSSWidth+'px; height:'+sidebarCSSHeight+'px; margin:'+sidebarPadding+'px; } ';
		var sidebarOrderCSS = ' .sidebar-order { height:'+(sidebarCSSHeight-520)+'px; overflow:hidden; } ';
		var slideCSS   = ' #photo-container, .photo { width:'+slideWidth+'px; height:'+slideHeight+'px; } ';
		var loadingCSS = ' .loading { left:'+((slideWidth/2)-64)+'px; top:'+((slideHeight/2)-64)+'px; } ';


		$('head').append('<style id="slides-style">' + sidebarCSS + sidebarOrderCSS + slideCSS + loadingCSS + '</style>');

	};

	this.loadSlidePage = function() {

		var _self = window.kuva.currentController;

		var $_HASH = window.kuva.hash.get( true );

		$.ajax({

			url:'/api/json/photos/'+$_HASH.R2+'/image',

			type:'GET',

			dataType: 'json',

			headers: {
				'AJAX': true,
				'ETAG': $('body').data('token')
			},

			success: function(rawPhotoURLs) {

				_self.guidePhotoSet.total = rawPhotoURLs.length;

				_self.exitOnFree();

				//update text in sidebar
				$('.totalPhotoNumber').text( _self.guidePhotoSet.total );

				//add photos to the dom
				for(var i=0; i<_self.guidePhotoSet.total; i++) {

					var url = rawPhotoURLs[i]+'/'+window.kuva.photoSet.width+'/'+window.kuva.photoSet.height;
					_self.guidePhotoSet.urls.push( url );

					//add the photo containers
					$('#slides-core #photo-container').jqotepre('#tmpl-slides-addPhotoToDom', { id:i, displayID:i+1 });

					//add this guide's photos from the photo-repository to the photo-container
					$('.photo[src="'+url+'"]').attr('data-guideindex', i).css('display','block').appendTo('#photoWrap'+i);

					//show the first image
					if(i===0) {
						$('#photoWrap'+i).fadeIn();
					}
				}

				_self.setInterval();

			}

		});

		if(window.kuva.order.get().length>0) {
			_self.updateOrder();
		}
	};

	this.updateURL = function(nextPrev) {
		if(typeof(nextPrev)=='undefined') {
			var nextPrev = 'next';
		}
		var _self = window.kuva.currentController;
		var hash = window.kuva.hash.get(true);
		var nextPhoto = _self.changePhoto( nextPrev, true );
		window.location.hash = '#/'+hash.R1+'/'+hash.R2+'/'+nextPhoto;
	};

	this.changePhoto = function( nextPrev, getNumber ) {
		var _self = window.kuva.currentController;

		var newPhoto = 0;

		if(!isNaN(nextPrev)) {
			newPhoto = nextPrev;
		}
		else {
			if(nextPrev=='next') {
				if(_self.currentPhoto < _self.guidePhotoSet.total-1) {
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
					newPhoto = parseInt(_self.guidePhotoSet.total)-1;
				}
			}
		}

		if(typeof(getNumber)!='undefined') {
			return newPhoto;
		}

		$('#photoWrap'+newPhoto).fadeIn();
		$('#photoWrap'+_self.currentPhoto).fadeOut(500);

		$('.currentPhotoNumber').text( parseInt(newPhoto)+1 );

		_self.currentPhoto = newPhoto;

	};

	this.setInterval = function() {
		var _self = window.kuva.currentController;
		_self.interval = window.clearInterval(_self.interval);
		_self.interval = setInterval(function(){

			var _self = window.kuva.currentController;

			_self.updateURL();

		}, _self.duration);
	};

	this.clearInterval = function() {
		var _self = window.kuva.currentController;
		_self.interval = window.clearInterval(_self.interval);
	};

	this.updateOrder = function( ) {
		$('.order-controls').show();
		$('.sidebar-order').show();
		$('.no-order-controls').hide();

		var totals = window.kuva.order.totals();

		$('.subtotal').text( parseFloat(totals.sub).toFixed(2) );
		$('.taxtotal').text( parseFloat(totals.tax).toFixed(2) );
		$('.grandtotal').text( parseFloat(totals.grand).toFixed(2) );

		var products = new window.kuva.products.get();

		var order = window.kuva.order.get();
		var rows = '';
		for(var i in order) {
			rows += '<tr><td>'+order[i].qty+'</td><td>'+order[i].name+'</td><td class="price">$'+(parseFloat(order[i].price)*parseInt(order[i].qty)).toFixed(2)+'</td>';
		}
		$('.items').html(rows);
	};

};

window.kuva.addController( slides );