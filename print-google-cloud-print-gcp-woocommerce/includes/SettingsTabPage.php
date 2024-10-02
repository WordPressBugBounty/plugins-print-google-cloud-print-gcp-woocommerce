<?php

namespace Zprint;

class SettingsTabPage extends TabPage
{
  protected function renderPageForm()
	{
		parent::renderContentPage();

		if(!(isset($this->args['hideForm']) && $this->args['hideForm'])) {
			$this->renderPageFormWithWrapper();
		};
	}

	private function renderPageFormWithWrapper()
	{
		?>
		<form action="options.php" method="POST" enctype="multipart/form-data">
			<?php
			settings_fields(self::getName($this));
			$this->do_settings_sections(self::getName($this));
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Same function as core do_settings_sections but with div wrapper
	 *
	 * @param string $page
	 */
	private function do_settings_sections($page)
	{
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[ $page ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
			$slug = str_replace(' ', '_', strtolower($section['title']));

			echo '<div class="zprint-settings-section-wrapper zprint-settings-section-' . $slug . '">';

			if ( $section['title'] ) {
				echo "<h2>{$section['title']}</h2>\n";
			}

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}

			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
				continue;
			}
			echo '<table class="form-table" role="presentation">';
			do_settings_fields( $page, $section['id'] );
			echo '</table>';

			echo '</div>';
		}
	}
}
