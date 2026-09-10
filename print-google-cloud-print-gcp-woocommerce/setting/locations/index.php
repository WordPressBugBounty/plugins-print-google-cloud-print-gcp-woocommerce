<?php

namespace Zprint;

use Zprint\Model\Location;

return function ($tab, $page) {
	$locations = array_map(function ($el) {
		return $el->getData();
	}, Location::getAll());

	$users = array_map(function ($location) {
		return $location['users'];
	}, $locations);

	$users = array_reduce($users, function ($a, $b) {
		return array_merge($a, $b);
	}, []);
	$users = array_unique($users);

	$users_values = array_map(function ($id) {
		return get_user_by('id', $id)->display_name;
	}, $users);
	$users = array_combine($users, $users_values);

	$printers = Printer::getPrinters(); ?>
	<div class="zprint-list-view">
		<table class="wp-list-table widefat fixed striped posts zprint-list">
			<tbody>
			<tr>
				<th scope="col" style="width: 200px;">
					<?php _e('Description', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
				<?php if (defined('\ZPOS\ACTIVE') && \ZPOS\ACTIVE): ?>
					<th scope="col" class="status">
						<?php _e('Automatic Print Website Order', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</th>
				<?php else: ?>
					<th scope="col" class="status">
						<?php _e('Enabled', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</th>
				<?php endif; ?>
				<?php if (defined('\ZPOS\ACTIVE') && \ZPOS\ACTIVE): ?>
					<th scope="col" class="status">
						<?php _e('Automatic Print POS Order', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</th>
				<?php endif; ?>
				<th scope="col">
					<?php _e('Printers', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
				<th scope="col">
					<?php _e('Users', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
				<th scope="col">
					<?php _e('Template', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
				<th scope="col">
					<?php _e('Size', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
				<th scope="col" style="width: 100px; text-align: right;">
					<?php _e('Actions', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
			</tr>
			<?php foreach ($locations as $location): ?>
				<tr>
					<td><span class="dashicons dashicons-exerpt-view" style="margin-right: 5px;"></span><strong><a
								href="<?= add_query_arg('id', $location['id'], $page->getUrl($tab)); ?>"
								class="row-title"><?= $location['title']; ?></a></strong></td>
					<td class="status">
						<?php if ($location['web_order']): ?>
							<span class="pm-badge pm-badge-success"><?php _e('Enabled', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></span>
						<?php else: ?>
							<span class="pm-badge pm-badge-muted"><?php _e('Disabled', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></span>
						<?php endif; ?>
					</td>
					<?php if (defined('\ZPOS\ACTIVE') && \ZPOS\ACTIVE): ?>
						<td class="status">
							<?php if ($location['pos_order_only']): ?>
								<span class="pm-badge pm-badge-success"><?php _e('Enabled', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></span>
							<?php else: ?>
								<span class="pm-badge pm-badge-muted"><?php _e('Disabled', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></span>
							<?php endif; ?>
						</td>
					<?php endif; ?>
					<td>
						<ul class="list">
							<?= apply_filters( 'zprint_admin_location_table_printer_label', implode(", ", array_map(function ($printer) use ($printers) {
								return isset($printers[$printer]) ? '<li>' . $printers[$printer] . '</li>' : '';
							}, $location['printers'])), $location ); ?>
						</ul>
					</td>
					<td>
						<?php if (!empty($location['autoIncludeAllUsers'])): ?>
							<strong><?php _e('All Users (Auto)', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></strong>
						<?php else: ?>
							<ul class="list">
								<?= implode(", ", array_map(function ($user) use ($users) {
									return '<li>' . $users[$user] . '</li>';
								}, $location['users'])); ?>
							</ul>
						<?php endif; ?>
					</td>
					<td>
						<?= Location::getTemplates()[$location['template']]; ?>
					</td>
					<td>
						<?php
						if ($location['size'] !== "custom") {
							echo Location::getSizes()[$location['size']['name']];
						} else {
							if ($location['width'] > 0) {
								echo 'W&nbsp;' . $location['width'] . __('mm', 'Print-Google-Cloud-Print-GCP-WooCommerce');
								echo ' &times; ';
								echo 'H&nbsp;' . ($location['height'] > 0 ? $location['height'] . __('mm', 'Print-Google-Cloud-Print-GCP-WooCommerce') : __('Auto', 'Print-Google-Cloud-Print-GCP-WooCommerce'));
							} else {
								echo __('Auto', 'Print-Google-Cloud-Print-GCP-WooCommerce');
							}
						} ?>
					</td>
					<td style="text-align: right;">
						<a href="<?= add_query_arg('id', $location['id'], $page->getUrl($tab)); ?>" class="button">
							<?php _e('Edit', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php if ( empty( $locations ) ) { ?>
		<div class="zprint-empty-list">
			<div class="zprint-empty-list__icon fa-solid fa-print"></div>
			<p class="zprint-empty-list__notice">
		  <?php
		  echo sprintf(
			  /* translators: %1$s: line break; %2$s: right-caret icon. */
			  esc_html_x(
				  'Create a printer location to send print jobs from: %1$s Your Store %2$s BizPrint %2$s your connected printer.',
				  '%1$s - line break, %2$s - caret right icon',
				  'Print-Google-Cloud-Print-GCP-WooCommerce'
			  ),
			  '<br/>',
			  '<span class="fa-solid fa-caret-right"></span>'
		  );
		  ?>
			</p>
			<p class="zprint-empty-list__line">
				<a class="zprint-empty-list__btn" href="<?php echo esc_url( add_query_arg('id', 'new', $page->getUrl($tab)) ); ?>">
					<?php echo esc_html__( 'Create your first printer location', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
				</a>
			</p>
			<p class="zprint-empty-list__line">
				<a class="zprint-empty-list__link" href="https://getbizprint.com/videos" target="_blank">
					<?php echo esc_html__( 'Watch a video', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
};
