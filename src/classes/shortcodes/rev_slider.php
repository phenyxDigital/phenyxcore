<?php
extract(Composer::shortcode_atts([
    'alias' => '', //standard, button_count, box_count
    'el_class'    => '',
    'display_mobile' => '',
    'display_tablet' => '0',
], $atts));
$context = Context::getContext();
if($display_mobile == 'not_display_mobile' && $context->isMobileDevice()) {
    return;
}
if($display_tablet == 'not_display_tablet' && $context->isTabletDevice()) {
    return;
}
$output = '';
$output = '[rev_slider alias="'.$alias.'"]';
echo $output;