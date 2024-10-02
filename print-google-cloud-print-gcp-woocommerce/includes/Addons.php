<?php

namespace Zprint;

defined('ABSPATH') or die('No script kiddies please!');

class Addons
{
	public static function render() {
		?>
			<div class="zprint-marketplace">
				<div class="zprint-marketplace__tile zprint-marketplace__tile_lg">
					<div class="zprint-marketplace__icon">
						<span class="fad fa-bags-shopping" style="--fa-primary-color: #429DBA;--fa-secondary-color: #8BC7DB;"></span>
					</div>
					<p class="zprint-marketplace__subtitle">
						<?php echo esc_html__('Our Full Collection of Print Templates', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</p>
					<h3 class="zprint-marketplace__title">
			  		<?php echo esc_html__('Marketplace', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</h3>
					<a class="zprint-marketplace__btn" href="https://getbizprint.com/marketplace/#templates" target="_blank">
			  		<?php echo esc_html__('Explore templates', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</a>
				</div>
				<div class="zprint-marketplace__tile zprint-marketplace__tile_lg">
					<div class="zprint-marketplace__icon">
						<span class="fad fa-puzzle" style="--fa-primary-color: #e3dcfa;--fa-secondary-color: #9b86e1;"></span>
					</div>
					<p class="zprint-marketplace__subtitle">
			  		<?php echo esc_html__('Extensions for Popular WooCommerce Plugins', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</p>
					<h3 class="zprint-marketplace__title">
			  		<?php echo esc_html__('Template Extensions', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</h3>
					<a class="zprint-marketplace__btn" href="https://getbizprint.com/marketplace/#extensions" target="_blank">
			  		<?php echo esc_html__('Shop extensions', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</a>
				</div>
				<div class="zprint-marketplace__tile zprint-marketplace__tile_md">
					<div class="zprint-marketplace__icon">
						<span class="fad fa-cubes" style="--fa-primary-color: #83a7e1;--fa-secondary-color: #cee1ff;"></span>
					</div>
					<h3 class="zprint-marketplace__title">
			  		<?php echo esc_html__('Add-ons', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</h3>
					<p class="zprint-marketplace__subtitle">
			  		<?php echo esc_html__('Custom Branding, Product Mapping & More', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</p>
					<a class="zprint-marketplace__btn" href="https://getbizprint.com/marketplace/#add-ons" target="_blank">
			  		<?php echo esc_html__('Shop add-ons', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</a>
				</div>
				<div class="zprint-marketplace__tile">
					<h3 class="zprint-marketplace__title">
			  		<?php echo esc_html__('Hardware', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</h3>
					<p class="zprint-marketplace__subtitle">
			  		<?php echo esc_html__('Hardware Designed for BizPrint', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</p>
					<a class="zprint-marketplace__btn" href="https://getbizprint.com/marketplace/#hardware" target="_blank">
			  		<?php echo esc_html__('View hardware', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</a>
					<div class="zprint-marketplace__icon">
						<span class="fad fa-print" style="--fa-primary-color: #67bf94;--fa-secondary-color: #c4ead8;"></span>
					</div>
				</div>
				<div class="zprint-marketplace__tile">
					<h3 class="zprint-marketplace__title">
						<?php echo esc_html__('Developers', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</h3>
					<p class="zprint-marketplace__subtitle">
			  		<?php echo esc_html__('Tools & Templates', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</p>
					<a class="zprint-marketplace__btn" href="https://getbizprint.com/marketplace/#developer" target="_blank">
			  		<?php echo esc_html__('Use tools', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</a>
					<div class="zprint-marketplace__icon">
						<span class="fad fa-screwdriver-wrench" style="--fa-primary-color: #d3d3d3;--fa-secondary-color: #000000;"></span>
					</div>
				</div>
			</div>
		<?php
	}
}
