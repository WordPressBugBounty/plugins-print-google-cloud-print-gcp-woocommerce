<?php

namespace Zprint;
class Input extends \Zprint\Aspect\Input
{
	const TYPE_FAKE_FILE = 'FakeFile';
	const TYPE_SMART_BUTTON = 'SmartButton';
	const TYPE_INFO = 'Info';
	const TYPE_SECRET_INPUT = 'SecretInput';
	const TYPE_PASSWORD = 'Password';
	const TYPE_CHECKBOX_EXTENDED = 'CheckboxExtended';
	const TYPE_API_SELECTOR = 'ApiSelector';

	public function label($post, $parent)
	{
		if (isset($this->args['label_for_disabled'])) {
			return '<label>' . $this->labels['singular_name'] . '</label>';
		}
		return parent::label($post, $parent);
	}

	public function renderInput($post, $parent)
	{
		if (isset($this->args['renderInput\before'])) {
			echo apply_filters(
				'\Aspect\Input\renderInput\before',
				$this->args['renderInput\before'],
				$this,
				$this->args
			);
		}
		parent::renderInput($post, $parent);
		if (isset($this->args['renderInput\after'])) {
			echo apply_filters(
				'\Aspect\Input\renderInput\after',
				$this->args['renderInput\after'],
				$this,
				$this->args
			);
		}
	}

	public function attributes($attrs)
	{
		$attrs = apply_filters('\Aspect\Input\attributes', $attrs);

		$attrs = array_map(
			function ($value, $key) {
				if ($value === null) {
					return null;
				}

				$key = esc_attr($key);
				$value = esc_attr($value);

				return "{$key}='{$value}'";
			},
			$attrs,
			array_keys($attrs)
		);

		$attrs = array_filter($attrs);

		return implode(' ', $attrs);
	}

	public function htmlFakeFile($post, $parent)
	{
		$value = $this->getValue($parent, 'attr', $post);
		$name = $this->nameInput($post, $parent);
		$id = $name;
		$attrs = compact('value', 'name', 'id');
		?>
				<input
					class="large-text code"
					type="file"
					<?= $this->attributes($attrs) ?>
				/>
				<?php
	}

	public function htmlSmartButton($post, $parent)
	{
		$name = $this->nameInput($post, $parent);
		$id = $name;

		$onclick = isset($this->args['onclick']) ? $this->args['onclick'] : null;

		$disabled = isset($this->args['disabled']) && $this->args['disabled'] ? 'disabled' : null;

		$attrs = compact('disabled', 'name', 'id', 'onclick');
		?>
				<button
					class="button"
					value="1"
					<?= $this->attributes($attrs) ?>
				>
						<?= isset($this->labels['button_name'])
      	? $this->labels['button_name']
      	: $this->labels['singular_name'] ?>
				</button>
				<?php
	}

	public function htmlText($post, $parent)
	{
		$name = $this->nameInput($post, $parent);
		$id = $name;
		$value = $this->getValue($parent, 'attr', $post);
		$disabled = isset($this->args['disabled']) && $this->args['disabled'] ? 'disabled' : null;

		$attrs = compact('value', 'name', 'id', 'disabled');
		?>
				<input
					class="large-text code"
					type="text" <?= $disabled ?>
					<?= $this->attributes($attrs) ?>
				/>
				<?php
	}

	public function htmlPassword($post, $parent)
	{
		$value = $this->getValue($parent, 'attr', $post); ?>
				<input class="large-text code" type="password"
							 name="<?= $this->nameInput($post, $parent) ?>"
							 id="<?= $this->nameInput($post, $parent) ?>"
							 value="<?= $value ?>" />
				<?php
	}

	public function htmlInfo()
	{
		echo is_callable($this->args['content'])
			? call_user_func($this->args['content'])
			: $this->args['content'];
	}

	public function htmlSecretInput($post, $parent)
	{
		$name = $this->nameInput($post, $parent);
		$id = $name;
		$value = $this->getValue($parent, 'attr', $post);
		$default = $this->args['default'];

		$attrs = compact('value', 'name', 'id');
		?>
				<input
					class="large-text code"
					type="text"
					<?= $this->attributes($attrs) ?>
				/>
				<script>
						jQuery(function($) {
								var toHide = '<?= esc_js($value) ?>' === '<?= esc_js($default) ?>';
								var tr = $('#<?= $id ?>').parents('tr');
								if (toHide) tr.hide();
								$(document).on('keyup', function(event) {
									if (event.ctrlKey &&
											event.shiftKey &&
											event.key === 'C') {
												tr.show();
										}
								});
						});
				</script>
				<?php
	}

	public function htmlCheckboxExtended($post, $parent)
	{
		$value = $this->getValue($parent, null, $post);
		$disabled_items = $this->args['disabled_items'];

		$i = 0;
		foreach ($this->attaches as $option) {
			$is_disabled = isset($disabled_items[$i]) && $disabled_items[$i];
			$attr_disabled = $is_disabled ? 'disabled="disabled' : '';
			if (is_array($option)) { ?>
				<label>
					<?php if ($is_disabled) { ?>
						<input type="hidden" name="<?= $this->nameInput($post, $parent) ?>[]" value="<?= esc_attr($option[0]) ?>" />
					<?php } ?>
					<input type="checkbox" <?php if($is_disabled) { echo 'checked'; } else { self::checked($value, esc_attr($option[0])); } ?>
							  name="<?= !$is_disabled ? ($this->nameInput($post, $parent) . '[]') : '' ?>"
							  value="<?= esc_attr($option[0]) ?>" <?= esc_attr($attr_disabled) ?>>&nbsp;<?= esc_html($option[1]) ?>
				</label>
			<?php } else { ?>
				<label>
					<?php if ($is_disabled) { ?>
						<input type="hidden" name="<?= $this->nameInput($post, $parent) ?>[]" value="<?= esc_attr($option) ?>" />
					<?php } ?>
					<input type="checkbox" <?php self::checked($value, esc_attr($option)); ?>
							  name="<?= !$is_disabled ? ($this->nameInput($post, $parent) . '[]') : '' ?>"
							  value="<?= esc_attr($option) ?>" <?= esc_attr($attr_disabled) ?>>&nbsp;<?= ucfirst(esc_html($option)) ?>
				</label>
			<?php
			}
			if(isset($this->args['divider'])) echo $this->args['divider'];

			$i++;
		}
	}

	public function saveBeforeApiSelector(&$data) {
		if($data === 'migrate-to-v1') {
			$response = RestClient::getRequest('printers', true);
			if ($response['headers']['Updated-Application-Version']) {
				$data = 'v1';
			} else {
				$data = 'v0';
			}
		}
	}

	public function htmlApiSelector($post, $parent)
	{
		$value = $this->getValue($parent, null, $post);
		$name = $this->nameInput($post, $parent);

		switch ($value) {
			case false: { ?>
                <label>
                    <input type="radio" name="<?= $name ?>" required value="v0">
                    Legacy API [Not recommended]
                </label>
                <label>
                    <input type="radio" name="<?= $name ?>" checked value="v1">
                    REST API
                </label>
            <?php break; }

			case 'v0': { ?>
                <div class="zprint-version-selector">
                    Legacy API
                    <input type="hidden" name="<?= $name ?>" checked value="v0">
                    <button class="button button-primary" name="<?= $name ?>" value="migrate-to-v1">
                        Migrate to REST API
                    </button>
                </div>
			<?php break; }

			case 'v1': {
			    echo '<input type="hidden" name="'.$name.'" value="v1">';
				echo 'REST API';
				break;
			}
		}
	}
}
