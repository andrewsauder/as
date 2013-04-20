var order = function() {

	this._sid = 'order';
	this._cache = false;

	this._ready = function(fromCache) {

		if (typeof(fromCache) == 'undefined') {
			var fromCache = false;
		}

		var _self = window.kuva.currentController;

		_self.layoutPage( );

		$('#order-core').on('click.order', '.decrease-qty', function() {
			var itemIndex = $(this).data('itemid');
			var curQty = window.kuva.order.items[itemIndex].qty;
			if (curQty > 1) {
				window.kuva.order.items[itemIndex].qty = curQty - 1;
				$('#item' + itemIndex).find('.current-qty').text(window.kuva.order.items[itemIndex].qty);
				$('#item' + itemIndex).find('.item-price').text((window.kuva.order.items[itemIndex].qty * window.kuva.order.items[itemIndex].price).toFixed(2));
				_self.updatetotals();
			}
		});

		$('#order-core').on('click.order', '.increase-qty', function() {
			var itemIndex = $(this).data('itemid');
			var curQty = window.kuva.order.items[itemIndex].qty;
			window.kuva.order.items[itemIndex].qty = curQty + 1;
			$('#item' + itemIndex).find('.current-qty').text(window.kuva.order.items[itemIndex].qty);
			$('#item' + itemIndex).find('.item-price').text((window.kuva.order.items[itemIndex].qty * window.kuva.order.items[itemIndex].price).toFixed(2));
			_self.updatetotals();
		});

		$('#order-core').on('click.order', '.remove-item', function() {
			var itemIndex = $(this).data('itemid');
			$('#item' + itemIndex).remove();
			delete window.kuva.order.items[itemIndex];
			_self.updatetotals();
		});

	};

	this._destroy = function() {
		$('#order-core').off('click.order');
	};

	this._hashChange = function(hash) {

		var _self = this;

		$('#order-core').empty();

		if (hash.R2 == 'complete') {
			_self.completeOrder();
		}
		else if (hash.R2 == 'cancel') {
			if (hash.R3 == 'confirmed') {
				_self.cancelOrder(true);
			}
			_self.cancelOrder(false);
		}
		else {
			_self.loadPage(false);
		}

	};

	this.layoutPage = function() {

		var _self = window.kuva.currentController;

		var slideCSS = ' #order-edit { height:' + (window.kuva.window.height - 100) + 'px; } ';

		$('head').append('<style id="order-style">' + slideCSS + '</style>');

	};

	this.loadPage = function(fromCache) {

		var _self = window.kuva.currentController;

		var items = window.kuva.order.get();
		var totals = window.kuva.order.totals();

		$('#order-core').css('background', '#fff');

		$('#order-core').jqoteapp('#tmpl-order-edit', {items: items, totals: totals});

	};

	this.updatetotals = function() {
		var totals = window.kuva.order.totals();
		$('.subtotal').text(totals.sub.toFixed(2));
		$('.taxes').text(totals.tax.toFixed(2));
		$('.grandtotal').text(totals.grand.toFixed(2));
	};

	this.completeOrder = function() {

		$('#order-core').empty();
		$('#order-core').jqoteapp('#tmpl-order-complete', orderInfo);

		var orderInfo = window.kuva.order.complete();

	};

	this.cancelOrder = function(confirmed) {

		if (!confirmed) {
			$('#order-core').empty();
			$('#order-core').jqoteapp('#tmpl-order-cancel', {});
			$('#order-cancel').height(window.kuva.window.height).width(window.kuva.window.width);
		}
		else {
			window.kuva.order.cancel();
			window.location.hash = '#/guides';
		}

	};

};

window.kuva.addController(order);