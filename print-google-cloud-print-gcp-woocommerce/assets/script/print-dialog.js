jQuery(function($) {
	var ajaxUrl = window.zprint_print_dialog.ajax_url;
	var $body = $('body');

	$('.zprint-open-print-dialog').on('click', function (e) {
		e.preventDefault();
		showDialog($(this).data('zprint-order-ids'));
	});

	$('#wc-orders-filter, #posts-filter').on('submit', function (e) {
		if ( 'zprint_print' !== $('#bulk-action-selector-top').val() ) {
			return;
		}

		e.preventDefault();

		var checkboxes = Object.values($('#the-list').find('.check-column input[type="checkbox"]'));
		var orderIDs = checkboxes.filter(function (checkbox) {
			return $(checkbox).is(':checked');
		}).map(function (checkbox) {
			return Number($(checkbox).val());
		});

		if (!orderIDs.length) {
			return;
		}

		showDialog(orderIDs);
	})

	function showDialog(orderIDs) {
		var $dialog = $body.find('.zprint-dialog');

		if (!$dialog.length) {
			$body.append('<div class="zprint-dialog"/>');
			$dialog = $body.find('.zprint-dialog');
			$dialog.append('<div class="zprint-dialog__loader"/>');
			$dialog.append('<div class="zprint-dialog__content">');
		}

		var $content = $body.find('.zprint-dialog__content');
		var $loader = $body.find('.zprint-dialog__loader');

		$loader.fadeIn(200);
		$dialog.fadeIn(200);
		$body.css('overflow', 'hidden');
		$.ajax({
			url: ajaxUrl,
			data: {
				action: 'zprint_render_print_dialog_window',
				order_ids: JSON.stringify(orderIDs),
			},
			success: function (response) {
				$content.html(response);
				$loader.fadeOut(200);
				$content.find('.zprint-dialog__window').fadeIn(200);

				var $locations = $content.find('.zprint-dialog__location-checkbox');
				var $submit = $content.find('.zprint-dialog__submit');

				$submit.focus();
				$locations.on('change', function () {
					$submit.prop('disabled', !$locations.is(':checked'));
				})

				if ($locations.is(':checked')) {
					$submit.prop('disabled', false);
				}

				$submit.on('click', print);
				$content.find('.zprint-dialog__hide').on('click', hideDialog);
			}
		});
	}

	$(document).on('keyup', function(e) {
		var $dialog = $body.find('.zprint-dialog');

		if (!$dialog.length) {
			return;
		}

		switch (e.keyCode) {
			case 27:
				e.preventDefault();
				hideDialog();
				break;
			case 13:
				if ($dialog.find('.zprint-dialog__location-checkbox').is(':checked')) {
					e.preventDefault();
					print();
				}
				break;
			default:
		}
	}).on('click', function (e) {
		if (
			!$body.find('.zprint-dialog__window').length ||
			$(e.target).closest('.zprint-dialog__window').length ||
			$(e.target).is('.zprint-dialog__window')
		) {
			return;
		}

		hideDialog();
	});

	function hideDialog() {
		var $dialog = $body.find('.zprint-dialog');

		$dialog.fadeOut(200, function () {
			$dialog.remove();
			$body.css('overflow', 'auto');
		});
	}

	function print() {
		var $form = $body.find('.zprint-dialog__form');
		var orderIDs = $form.data('zprint-order-ids');
		var locations = Object.values($form.find('.zprint-dialog__location-checkbox'));
		var locationIDs = Object.values(locations.filter(function (location) {
			return $(location).is(':checked');
		}).map(function (location) {
			return Number($(location).val());
		}));

		if (!orderIDs.length || !locationIDs.length) {
			return;
		}

		$body.find('.zprint-dialog__window').fadeOut(200);
		$body.find('.zprint-dialog__loader').fadeIn(200);
		$.ajax({
			url: ajaxUrl,
			data: {
				action: 'zprint_print_manually',
				order_ids: JSON.stringify(orderIDs),
				location_ids: JSON.stringify(locationIDs),
				redirect_to: window.location.href,
			},
			success: function (response) {
				if (response.hasOwnProperty('data') && '' !== response.data) {
					window.location.assign(response.data);
				} else {
					window.location.refresh();
				}
			}
		});
	}
});
