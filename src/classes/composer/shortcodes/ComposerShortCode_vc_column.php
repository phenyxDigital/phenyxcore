<?php


class ComposerShortCode_vc_column extends ComposerShortCode {

	protected $predefined_atts = [
		'font_color'  => '',
		'el_class'    => '',
		'el_position' => '',
		'width'       => '1/1',
	];

	public function getColumnControls($controls, $extended_css = '') {

		$controls_start = '<div class="vc_controls vc_controls-visible controls' . (!empty($extended_css) ? " {$extended_css}" : '') . '">';
		$controls_end = '</div>';
		$vc_manager = ephenyx_manager();

		if ($extended_css == 'bottom-controls') {
			$control_title = $this->l('Append to this column');
		} else {
			$control_title = $this->l('Prepend to this column');
		}

		$controls_add = ' <a class="vc_control column_add" href="#" title="' . $control_title . '"><i class="fa-duotone fa-plus"></i></a>';
		$controls_edit = ' <a class="vc_control column_edit" href="#" title="' . $this->l('Edit this column') . '"><i class="fa-duotone fa-pen-to-square"></i></i></a>';
		$controls_delete = ' <a class="vc_control column_delete" href="#" title="' . $this->l('Delete this column') . '"><i class="fa-duotone fa-trash"></i></a>';

		return $controls_start . $controls_add . $controls_edit . $controls_delete . $controls_end;
	}

	public function singleParamHtmlHolder($param, $value) {

		$output = '';
		
		$old_names = ['yellow_message', 'blue_message', 'green_message', 'button_green', 'button_grey', 'button_yellow', 'button_blue', 'button_red', 'button_orange'];
		$new_names = ['alert-block', 'alert-info', 'alert-success', 'btn-success', 'btn', 'btn-info', 'btn-primary', 'btn-danger', 'btn-warning'];
		$value = str_ireplace($old_names, $new_names, $value);
		
		$param_name = isset($param['param_name']) ? $param['param_name'] : '';
		$type = isset($param['type']) ? $param['type'] : '';
		$class = isset($param['class']) ? $param['class'] : '';

		if (isset($param['holder']) == true && $param['holder'] != 'hidden') {
			$output .= '<' . $param['holder'] . ' class="wpb_vc_param_value ' . $param_name . ' ' . $type . ' ' . $class . '" name="' . $param_name . '">' . $value . '</' . $param['holder'] . '>';
		}

		return $output;
	}

	public function contentAdmin($atts, $content = null) {

		$width = $el_class = '';
		
		$atts = Composer::shortcode_atts($this->predefined_atts, $atts);
		extract($atts);
		$output = '';

		$column_controls = $this->getColumnControls($this->settings('controls'));
		$column_controls_bottom = $this->getColumnControls('add', 'bottom-controls');

		if ($width == 'column_14' || $width == '1/4') {
			$width = ['vc_col-sm-3'];
		} else
		if ($width == 'column_14-14-14-14') {
			$width = ['vc_col-sm-3', 'vc_col-sm-3', 'vc_col-sm-3', 'vc_col-sm-3'];
		} else
		if ($width == 'column_13' || $width == '1/3') {
			$width = ['vc_col-sm-4'];
		} else
		if ($width == 'column_13-23') {
			$width = ['vc_col-sm-4', 'vc_col-sm-8'];
		} else
		if ($width == 'column_13-13-13') {
			$width = ['vc_col-sm-4', 'vc_col-sm-4', 'vc_col-sm-4'];
		} else
		if ($width == 'column_12' || $width == '1/2') {
			$width = ['vc_col-sm-6'];
		} else
		if ($width == 'column_12-12') {
			$width = ['vc_col-sm-6', 'vc_col-sm-6'];
		} else
		if ($width == 'column_23' || $width == '2/3') {
			$width = ['vc_col-sm-8'];
		} else
		if ($width == 'column_34' || $width == '3/4') {
			$width = ['vc_col-sm-9'];
		} else
		if ($width == 'column_16' || $width == '1/6') {
			$width = ['vc_col-sm-2'];
		} else
		if ($width == 'column_56' || $width == '5/6') {
			$width = ['vc_col-sm-10'];
		} else {
			$width = [''];
		}

		for ($i = 0; $i < count($width); $i++) {
			$output .= '<div ' . $this->mainHtmlBlockParams($width, $i) . '>';
			$output .= str_replace("%column_size%", translateColumnWidthToFractional($width[$i]), $column_controls);
			$output .= '<div class="wpb_element_wrapper">';
			$output .= '<div ' . $this->containerHtmlBlockParams($width, $i) . '>';
			$output .= Composer::do_shortcode(Composer::shortcode_unautop($content));
			$output .= '</div>';

			if (isset($this->settings['params'])) {
				$inner = '';

				foreach ($this->settings['params'] as $param) {
					$param_value = isset($atts[$param['param_name']]) ? $atts[$param['param_name']] : '';

					if (is_array($param_value)) {
						// Get first element from the array
						reset($param_value);
						$first_key = key($param_value);
						$param_value = $param_value[$first_key];
					}

					$inner .= $this->singleParamHtmlHolder($param, $param_value);
				}

				$output .= $inner;
			}

			$output .= '</div>';
			$output .= str_replace("%column_size%", translateColumnWidthToFractional($width[$i]), $column_controls_bottom);
			$output .= '</div>';
		}
        
		return $output;
	}

	public function customAdminBlockParams() {

		return '';
	}

	public function mainHtmlBlockParams($width, $i) {
       
		return 'data-element_type="' . $this->settings["base"] . '" data-vc-column-width="' . vc_get_column_width_indent($width[$i]) . '" class="wpb_' . $this->settings['base'] . ' wpb_sortable eph_content_holder ' . $this->templateWidth() . '  "' . $this->customAdminBlockParams();
	}

	public function containerHtmlBlockParams($width, $i) {

		return 'class="wpb_column_container vc_container_for_children"';
	}

	public function template($content = '') {

		return $this->contentAdmin($this->atts);
	}

	protected function templateWidth() {

		return '<%= window.vc_convert_column_size(params.width) %>';
	}

	public function buildStyle($font_color = '') {

		$style = '';

		if (!empty($font_color)) {
			$style .= get_css_color('color', $font_color);
		}

		return empty($style) ? $style : ' style="' . $style . '"';
	}

}
