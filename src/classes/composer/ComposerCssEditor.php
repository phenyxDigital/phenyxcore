<?php

class ComposerCssEditor extends Composer {

	protected static $css_instance;
	protected $js_script_appended = false;
	protected $settings = [];
	protected $value = '';
	protected $layers = ['margin', 'border', 'padding', 'content'];
	protected $positions = ['top', 'right', 'bottom', 'left'];

	public static function getInstance() {

		if (!ComposerCssEditor::$css_instance) {
			ComposerCssEditor::$css_instance = new ComposerCssEditor();
		}

		return ComposerCssEditor::$css_instance;
	}

	public function settings($settings = null) {

		if (is_array($settings)) {
			$this->settings = $settings;
		}

		return $this->settings;
	}

	public function setting($key) {

		return isset($this->settings[$key]) ? $this->settings[$key] : '';
	}

	public function value($value = null) {

		if (is_string($value)) {
			$this->value = $value;
		}

		return $this->value;
	}

	public function params($values = null) {

		if (is_array($values)) {
			$this->params = $values;
		}

		return $this->params;
	}

	public function render() {
		$vc_manager = ephenyx_manager();
		$output = '<div class="vc_css-editor vc_row" data-css-editor="true">';
		$output .= $this->onionLayout();
		$output .= '<div class="vc_col-xs-5 vc_settings">'
		. '    <label>' . $this->l('Border') . '</label> '
		. '    <div class="color-group"><input type="text" name="border_color" value="" class="vc_color-control"></div>'
		. '    <div class="vc_border-style"><select name="border_style" class="vc_border-style">' . $this->getBorderStyleOptions() . '</select></div>'
		. '    <label>' . $this->l('Background') . '</label>'
		. '    <div class="color-group"><input type="text" name="background_color" value="" class="vc_color-control"></div>'
		. '    <div class="vc_background-image wpb_el_type_attach_image">' . $this->getBackgroundImageControl() . '<div class="vc_clearfix"></div></div>'
		. '    <div class="vc_background-style"><select name="background_style" class="vc_background-style">' . $this->getBackgroundStyleOptions() . '</select></div>'
		. '    <label>' . $this->l('Box controls') . '</label>'
		. '    <label class="vc_checkbox"><input type="checkbox" name="simply" class="vc_simplify" value=""> ' . $this->l('Simplify controls') . '</label>'
			. '</div>';
		$output .= '<input name="' . $this->setting('param_name') . '" class="wpb_vc_param_value  ' . $this->setting('param_name') . ' ' . $this->setting('type') . '_field" type="hidden" value="' . $this->esc_attr($this->value()) . '"/>';
		$output .= '</div><div class="vc_clearfix"></div>';
		$output .= '<script type="text/html" id="vc_css-editor-image-block">'
			. '<li class="added">'
			. '  <div class="inner" style="width: 75px; height: 75px; overflow: hidden;text-align: center;">'
			. '    <img src="{{ img.url }}?id={{ img.id }}" data-image-id="{{ img.id }}" class="vc_ce-image<# if(!_.isUndefined(img.css_class)) {#> {{ img.css_class }}<# }#>">'
			. '  </div>'
			. '  <a href="#" class="icon-remove"></a>'
			. '</li>'
			. '</script>';

		if (!$this->js_script_appended) {
			$output .= "\n\n" . '<doscript>JS::' . _EPH_JS_DIR_ . 'composer/params/css_editor.js' . '</doscript>';
			$this->js_script_appended = true;
		}

		return $output;
	}

	public function getBackgroundImageControl() {

		return '<ul class="vc_image">'
			. '</ul>'
			. '<a class="gallery_widget_add_images" href="#" use-back="true" use-single="true" title="Add image">Add image</a>';
	}

	public function getBorderStyleOptions() {
		$vc_manager = ephenyx_manager();
		$output = '<option value="">' . $this->l('Theme defaults') . '</option>';
		$styles = ['solid', 'dotted', 'dashed', 'none', 'hidden', 'double', 'groove', 'ridge', 'inset', 'outset', 'initial', 'inherit'];

		foreach ($styles as $style) {
			$output .= '<option value="' . $style . '">' . ucfirst($style) . '</option>';
		}

		return $output;
	}

	public function getBackgroundStyleOptions() {
		$vc_manager = ephenyx_manager();
		$output = '<option value="">' . $this->l('Theme defaults') . '</option>';
		$styles = [
			$this->l("Cover")     => 'cover',
			$this->l('Contain')   => 'contain',
			$this->l('No Repeat') => 'no-repeat',
			$this->l('Repeat')    => 'repeat',
		];

		foreach ($styles as $name => $style) {
			$output .= '<option value="' . $style . '">' . $name . '</option>';
		}

		return $output;
	}

	public function onionLayout() {

		$output = '<div class="vc_layout-onion vc_col-xs-7">'
		. '    <div class="vc_margin">' . $this->layerControls('margin')
		. '      <div class="vc_border">' . $this->layerControls('border', 'width')
		. '          <div class="vc_padding">' . $this->layerControls('padding')
			. '              <div class="vc_content"><i></i></div>'
			. '          </div>'
			. '      </div>'
			. '    </div>'
			. '</div>';
		return $output;
	}

	protected function layerControls($name, $prefix = '') {

		$output = '<label>' . $name . '</label>';

		foreach ($this->positions as $pos) {
			$output .= '<input type="text" name="' . $name . '_' . $pos . ($prefix != '' ? '_' . $prefix : '') . '" data-name="' . $name . ($prefix != '' ? '-' . $prefix : '') . '-' . $pos . '" class="vc_' . $pos . '" placeholder="-" data-attribute="' . $name . '" value="">';
		}

		return $output;
	}

}
