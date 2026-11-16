jQuery(function ($) {
	var config = window.zprint_quick_print || {};
	if (!config.restUrl) {
		return;
	}

	var $root = $('.zprint-quick-print');
	if (!$root.length) {
		return;
	}

	var $form = $root.find('.zprint-quick-print__form');
	var $dropzone = $form.find('[data-zprint-dropzone]');
	var $file = $form.find('[data-zprint-file]');
	var $selected = $form.find('[data-zprint-selected]');
	var $filename = $form.find('[data-zprint-filename]');
	var $filesize = $form.find('[data-zprint-filesize]');
	var $remove = $form.find('[data-zprint-remove]');
	var $printer = $form.find('[data-zprint-printer]');
	var $description = $form.find('[data-zprint-description]');
	var $submit = $form.find('[data-zprint-submit]');
	var $progress = $form.find('[data-zprint-progress]');
	var $progressFill = $form.find('[data-zprint-progress-fill]');
	var $progressLabel = $form.find('[data-zprint-progress-label]');
	var $status = $form.find('[data-zprint-status]');
	var $statusText = $form.find('[data-zprint-status-text]');
	var $hint = $form.find('[data-zprint-accept-hint]');

	var i18n = window.zprintI18n;
	var allowedExts = (config.formats || []).map(function (f) {
		return String(f.ext || '').toLowerCase();
	});
	var allowedMimes = (config.formats || []).map(function (f) {
		return String(f.mime || '').toLowerCase();
	});
	var maxBytes = Number(config.maxBytes) || 0;

	$hint.text(
		i18n.t('quick_print_accepted_formats', 'Accepted: {{formats}}. Max: {{size}} MB.', {
			formats: (config.formatLabels || []).join(', '),
			size: config.maxMb,
		})
	);

	var selectedFile = null;

	function fileExt(name) {
		var idx = String(name).lastIndexOf('.');
		return idx > -1 ? String(name).substring(idx + 1).toLowerCase() : '';
	}

	function isTypeAllowed(file) {
		var ext = fileExt(file.name);
		var mime = (file.type || '').toLowerCase();
		var extOk = allowedExts.indexOf(ext) !== -1;
		var mimeOk = !mime || allowedMimes.indexOf(mime) !== -1;
		return extOk && mimeOk;
	}

	function formatBytes(n) {
		if (n >= 1024 * 1024) {
			return (n / (1024 * 1024)).toFixed(2) + ' MB';
		}
		if (n >= 1024) {
			return (n / 1024).toFixed(1) + ' KB';
		}
		return n + ' B';
	}

	function setStatus(message, tone) {
		if (!message) {
			$status.prop('hidden', true);
			$statusText.text('');
			$status.removeClass('is-error is-success is-info');
			return;
		}

		$status
			.removeClass('is-error is-success is-info')
			.prop('hidden', false);
		$statusText.text(message);

		if (tone === 'error') {
			$status.addClass('is-error');
		} else if (tone === 'success') {
			$status.addClass('is-success');
		} else {
			$status.addClass('is-info');
		}
	}

	function setProgress(percent, label) {
		if (percent === null) {
			$progress.prop('hidden', true);
			$progressFill.css('width', '0%');
			$progressLabel.text('');
			return;
		}
		$progress.prop('hidden', false);
		$progressFill.css('width', Math.max(0, Math.min(100, percent)) + '%');
		if (label) {
			$progressLabel.text(label);
		}
	}

	/* Status tracks the post-submit success banner so we don't clobber it
	   with pre-submit hints (file ready / ready to print). It sticks until
	   the user interacts with the form again (new file, printer change,
	   remove file). Pre-submit hints are safe to overwrite. */
	var statusSticky = false;

	function clearStickyStatus() {
		if (statusSticky) {
			statusSticky = false;
			setStatus('', null);
		}
	}

	function refreshReadyStatus() {
		if (statusSticky) return;
		if (!selectedFile) {
			setStatus('', null);
			return;
		}
		if ($printer.val()) {
			setStatus(i18n.t('quick_print_ready', 'Ready to print.'), 'info');
		} else {
			setStatus(i18n.t('quick_print_file_ready', 'File ready. Pick a printer and send.'), 'info');
		}
	}

	function updateSubmitState() {
		var ready = !!selectedFile && !!$printer.val() && !$submit.data('in-flight');
		$submit.prop('disabled', !ready);
	}

	function setSelected(file) {
		selectedFile = file;
		if (file) {
			$filename.text(file.name);
			$filesize.text(formatBytes(file.size));
			$selected.prop('hidden', false);
			$dropzone.addClass('has-file');
		} else {
			$filename.text('');
			$filesize.text('');
			$selected.prop('hidden', true);
			$dropzone.removeClass('has-file');
			$file.val('');
		}
		setProgress(null);
		clearStickyStatus();
		refreshReadyStatus();
		updateSubmitState();
	}

	function pickFile(file) {
		if (!file) {
			return;
		}

		if (!isTypeAllowed(file)) {
			setSelected(null);
			setStatus(i18n.t('quick_print_unsupported_type', 'Only PDF and HTML files are accepted.'), 'error');
			return;
		}

		if (maxBytes && file.size > maxBytes) {
			setSelected(null);
			setStatus(i18n.t('quick_print_too_large', 'That file is larger than the allowed limit of {{size}} MB.', { size: config.maxMb }), 'error');
			return;
		}

		setSelected(file);
	}

	/* Drag/drop handling on the <label> dropzone. Native click-to-browse
	   comes from the label wrapping the file input — no JS needed. */
	$dropzone.on('dragenter dragover', function (e) {
		e.preventDefault();
		e.stopPropagation();
		$dropzone.addClass('is-dragover');
	});

	$dropzone.on('dragleave dragend drop', function (e) {
		e.preventDefault();
		e.stopPropagation();
		$dropzone.removeClass('is-dragover');
	});

	$dropzone.on('drop', function (e) {
		var dt = e.originalEvent && e.originalEvent.dataTransfer;
		if (!dt || !dt.files || !dt.files.length) {
			return;
		}
		/* Sync the dropped file onto the input so the native label flow stays consistent. */
		try {
			var dtObj = new DataTransfer();
			dtObj.items.add(dt.files[0]);
			$file[0].files = dtObj.files;
		} catch (err) {
			/* DataTransfer not supported here — the pickFile path still handles it. */
		}
		pickFile(dt.files[0]);
	});

	$file.on('change', function () {
		var file = this.files && this.files[0];
		if (file) pickFile(file);
	});

	$remove.on('click', function (e) {
		e.preventDefault();
		e.stopPropagation();
		setSelected(null);
	});

	$printer.on('change', function () {
		clearStickyStatus();
		refreshReadyStatus();
		updateSubmitState();
	});

	$form.on('submit', function (e) {
		e.preventDefault();

		if (!selectedFile) {
			setStatus(i18n.t('quick_print_need_file', 'Choose a file first.'), 'error');
			return;
		}
		if (!$printer.val()) {
			setStatus(i18n.t('quick_print_need_printer', 'Choose a destination printer.'), 'error');
			return;
		}

		var data = new FormData();
		data.append('file', selectedFile);
		data.append('printer_id', $printer.val());
		data.append('description', $description.val() || '');

		$submit.data('in-flight', true);
		$submit.addClass('is-loading');
		updateSubmitState();

		setProgress(0, i18n.t('quick_print_uploading', 'Uploading file…'));
		setStatus(i18n.t('quick_print_uploading', 'Uploading file…'), 'info');

		$.ajax({
			url: config.restUrl,
			method: 'POST',
			data: data,
			processData: false,
			contentType: false,
			headers: { 'X-WP-Nonce': config.nonce },
			xhr: function () {
				var xhr = new window.XMLHttpRequest();
				if (xhr.upload) {
					xhr.upload.addEventListener('progress', function (evt) {
						if (!evt.lengthComputable) return;
						var pct = Math.round((evt.loaded / evt.total) * 100);
						setProgress(pct, i18n.t('quick_print_uploading_percent', 'Uploading file… {{percent}}%', { percent: pct }));
						if (pct >= 100) {
							setProgress(100, i18n.t('quick_print_sending', 'Sending to printer…'));
							setStatus(i18n.t('quick_print_sending', 'Sending to printer…'), 'info');
						}
					});
				}
				return xhr;
			},
		}).done(function (res) {
			$submit.data('in-flight', false);
			$submit.removeClass('is-loading');
			setProgress(null);

			if (res && res.success) {
				/* Reset form state but hold the success banner in place —
				   setSelected(null) would normally clear status, so mark
				   it sticky first so refreshReadyStatus/clearStickyStatus
				   leave it alone until the next user action. */
				selectedFile = null;
				$filename.text('');
				$filesize.text('');
				$selected.prop('hidden', true);
				$dropzone.removeClass('has-file');
				$file.val('');
				$description.val('');

				statusSticky = true;
				setStatus(i18n.t('quick_print_sent', 'Print job sent successfully.'), 'success');
			} else {
				statusSticky = false;
				setStatus(i18n.t('quick_print_generic_error', 'Something went wrong. Please try again.'), 'error');
			}
			updateSubmitState();
		}).fail(function (xhr) {
			$submit.data('in-flight', false);
			$submit.removeClass('is-loading');
			setProgress(null);

			var message = i18n.t('quick_print_generic_error', 'Something went wrong. Please try again.');
			if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
				message = xhr.responseJSON.message;
			}
			statusSticky = false;
			setStatus(message, 'error');
			updateSubmitState();
		});
	});

	/* If a default printer is pre-selected on page load, reflect that in
	   updateSubmitState so the submit enables as soon as a file is dropped. */
	updateSubmitState();
});
