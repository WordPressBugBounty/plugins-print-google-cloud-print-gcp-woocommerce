<?php

namespace Zprint\Debug;

defined('ABSPATH') or die('No script kiddies please!');

class Tab
{
	public static function render($orders_ids = [], $locations = [])
	{
		?>
		<div class="zprint-debug-tab">
			<h2>
					<span class="zprint-setting-title">
							<span class="zprint-setting-title__text">
									<?php echo esc_html__('Requests', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
							</span>
							<a class="zprint-setting-title__link" href="https://bizswoop.atlassian.net/servicedesk/customer/portal/4/group/4/create/10039" target="_blank">
									<?php echo esc_html__('Create a support request', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
									<span class="fas fa-external-link"></span>
							</a>
					</span>
			</h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<button id="zprint-debug-remote-printers" class="button button-primary">
								<?php _e('Get Remote Printers', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
							</button>
						</th>
						<td>
							<textarea
								id="zprint-debug-remote-printers-output"
								rows="5"
								style="resize: none; width: 100%; text-align: left"
								readonly
							></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<table role="presentation">
								<tbody>
									<tr>
										<th scope="row">
											<label for="zprint-debug-orders"><?php _e('Order:', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></label>
										</th>
										<td>
											<select id="zprint-debug-orders">
												<?php foreach ($orders_ids as $order_id): ?>
													<option value="<?php echo $order_id; ?>"><?php echo '#' . $order_id; ?></option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="zprint-debug-locations"><?php _e('Location:', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></label>
										</th>
										<td>
											<select id="zprint-debug-locations">
												<?php foreach ($locations as $location): ?>
													<option value="<?php echo $location['id']; ?>"><?php echo $location['title']; ?></option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
							<button id="zprint-debug-print-request" class="button button-primary">
								<?php _e('Send Print Request', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
							</button>
						</th>
						<td>
							<textarea
								id="zprint-debug-print-request-output"
								rows="5"
								style="resize: none; width: 100%; text-align: left"
								readonly
							></textarea>
						</td>
					</tr>
				</tbody>
			</table>
			<?php self::renderScripts(); ?>
		</div>
		<?php
	}

	private static function renderScripts()
	{
		?>
			<script>
				(function ($) {
					$('#zprint-debug-remote-printers').on('click', function () {
						$.post(window.ajaxurl, {action: 'getPrinters'},
							function (data) {
								stringifyResponse('#zprint-debug-remote-printers-output', data);
							}
						);
					});

					$('#zprint-debug-print-request').on('click', function () {
						var data = {
							order_id: $('#zprint-debug-orders').val(),
							location_id: $('#zprint-debug-locations').val()
						}

						$.post(window.ajaxurl, {action: 'sendPrintRequest', data: data},
							function (data) {
								stringifyResponse('#zprint-debug-print-request-output', data);
							}
						);
					});

					function stringifyResponse(selector, data) {
						$(selector).text(
							JSON.stringify(data.data, undefined, 4)
						);
					}
				})(jQuery);
			</script>
		<?php
	}
}
