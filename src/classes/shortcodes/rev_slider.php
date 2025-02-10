<?php
extract(Composer::shortcode_atts([
    'alias' => '', //standard, button_count, box_count
    'el_class'    => '',
    'display_mobile' => 1,
    'display_tablet' => 1,
], $atts));
$context = Context::getContext();
if(!$display_mobile && $context->isMobileDevice()) {
    return;
}
if(!$display_tablet && $context->isTabletDevice()) {
    return;
}
$output = '';
$output = '[rev_slider alias="'.$alias.'"]';
echo $output;