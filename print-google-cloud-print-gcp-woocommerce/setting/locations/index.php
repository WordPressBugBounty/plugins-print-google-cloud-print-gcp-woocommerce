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
	<style>
		.zprint-list {
			margin-top: 10px;
		}

		.zprint-list td {
			line-height: 30px;
			position: relative;
		}

		.zprint-list .dashicons {
			margin-top: 5px;
		}

		.widefat .status {
			width: 120px;
			text-align: center;
		}

		.zprint-list .list {
			margin: 0;
			margin-top: -5px;
			left: 0;
			right: 0;
			max-height: 30px;
			line-height: 30px;
			padding: 5px;
			overflow: hidden;
			position: absolute;
			background: white;
			text-overflow: ellipsis;
		}

		.zprint-list tr:nth-child(2n+1) .list {
			background: #f9f9f9;
		}

		.zprint-list .list:hover {
			max-height: none;
			z-index: 2;
		}

		.zprint-list .list li {
			display: inline;
			word-wrap: break-spaces;
			margin-bottom: 0;
		}

		.zprint-list {
			min-width: 1024px;
		}
		.zprint-list-view {
			overflow-y: auto;
		}
	</style>
	<div class="zprint-list-view">
		<table class="wp-list-table widefat fixed striped posts zprint-list">
			<tbody>
			<tr>
				<th style="width: 200px;">
					<?php _e('Description', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
				<?php if (defined('\ZPOS\ACTIVE') && \ZPOS\ACTIVE): ?>
					<th class="status">
						<?php _e('Automatic Print Website Order', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</th>
				<?php else: ?>
					<th class="status">
						<?php _e('Enabled', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</th>
				<?php endif; ?>
				<?php if (defined('\ZPOS\ACTIVE') && \ZPOS\ACTIVE): ?>
					<th class="status">
						<?php _e('Automatic Print POS Order', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</th>
				<?php endif; ?>
				<th>
					<?php _e('Printers', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
				<th>
					<?php _e('Users', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
				<th>
					<?php _e('Template', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
				<th>
					<?php _e('Size', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
				<th style="width: 100px; text-align: right;">
					<?php _e('Actions', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
			</tr>
			<?php foreach ($locations as $location): ?>
				<tr>
					<td><span class="dashicons dashicons-exerpt-view" style="margin-right: 5px;"></span><strong><a
								href="<?= add_query_arg('id', $location['id'], $page->getUrl($tab)); ?>"
								class="row-title"><?= $location['title']; ?></a></strong></td>
					<td class="status">
						<span class="dashicons dashicons-<?= ($location['web_order'] ? 'yes' : 'minus') ?>"></span>
					</td>
					<?php if (defined('\ZPOS\ACTIVE') && \ZPOS\ACTIVE): ?>
						<td class="status">
							<span class="dashicons dashicons-<?= ($location['pos_order_only'] ? 'yes' : 'minus') ?>"></span>
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
		<style>
		.zprint-empty-list {
			margin-top: 40px;
			text-align: center;
		}

		.zprint-empty-list__icon {
			font-size: 170px;
			color: #DDDDDD;
		}

		.zprint-empty-list__notice {
			margin: 30px 0 25px;
			font-size: 18px;
			color: #B3B3B3;
		}

		.zprint-empty-list__notice span {
			margin: 0 3px;
			font-size: 13px;
		}

		.zprint-empty-list__line {
			margin: 0 0 15px;
		}

		.zprint-empty-list__line:last-child {
			margin: 0;
		}

		.zprint-empty-list__btn {
			display: inline-block;
			padding: 16px 28px;
			font-size: 16px;
			color: #FFF;
			text-decoration: none;
			background: #6EC39A;
			border-radius: 3px;
		}

		.zprint-empty-list__btn:hover,
		.zprint-empty-list__btn:focus,
		.zprint-empty-list__btn:active {
			color: #FFF;
			background: #48AD7C;
			box-shadow: none;
		}

		.zprint-empty-list__link {
			font-size: 14px;
		}
		</style>
		<div class="zprint-empty-list">
			<div class="zprint-empty-list__icon fa-solid fa-print"></div>
			<p class="zprint-empty-list__notice">
		  <?php
		  echo sprintf(
			  esc_html_x(
				  'Create a printer location to send print jobs from: %1$s WooCommerce %2$s BizPrint %2$s your connected printer.',
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


