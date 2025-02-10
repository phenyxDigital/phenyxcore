<?php
extract(Composer::shortcode_atts([
    'alias' => '', //standard, button_count, box_count
    'el_class'    => '',
], $atts));
$el_class = $this->getExtraClass($el_class);
$output = '<div class="' . $el_class . '>';
$output .= '[rev_slider alias="'.$alias.'"]';
$output .= '</div>';
echo $output;