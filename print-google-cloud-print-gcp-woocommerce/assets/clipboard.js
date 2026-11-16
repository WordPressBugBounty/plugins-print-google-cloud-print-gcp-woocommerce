jQuery(function ($) {
	'use strict';

	if (!navigator.clipboard) {
		return;
	}

	var i18n = window.zprintI18n;
	if (!i18n || typeof i18n.t !== 'function') {
		i18n = {
			t: function (key, fallback) {
				return fallback || key;
			},
		};
	}
	var $copyButtons = $('.zprint-copy-log-button');

	$copyButtons.show();

	$copyButtons.on('click', function () {
		$.post(window.ajaxurl, {
			action: 'zprint_copy_log',
			type: this.dataset.logType,
			nonce: this.dataset.nonce,
		}).done(function (data) {
			navigator.clipboard.writeText(data).then(
				function () {
					alert(i18n.t('copied_successfully', 'Copied successfully!'));
				},
				function () {
					alert(i18n.t('something_went_wrong', 'Something went wrong!'));
				},
			);
		});
	});
});
