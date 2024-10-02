jQuery(function($) {
	if (!navigator.clipboard) {
		return;
	}

	var $copyButtons = $('.zprint-copy-log-button');

	$copyButtons.show();

	$copyButtons.on('click', function () {
		$.post(
			window.ajaxurl,
			{action: 'zprint_copy_log', type: this.dataset.logType}
		).done(function (data) {
			navigator.clipboard
				.writeText(data)
				.then(function () {
					alert('Copied successfully!');
				},function () {
					alert('Something went wrong!');
				});
		});
	});
});
