<?php
$output = $el_class = $image = '' ;

extract(vc_map_get_attributes($this->getShortcode(), $atts));

$el_class = $this->getExtraClass($el_class);
$img_id = preg_replace('/[^\d]/', '', $image);
$img = getImageBySize(['attach_id' => $img_id, 'class' => 'jarallax-img']);

$css_class = 'wpb_text_column  ' . $el_class;



$output .= "\n\t" . '<div class="jarallax_'.$img_id.'"><div class="' . $css_class . '">';
$output .= "\n\t\t" . '<div class="wpb_wrapper" style="color:'.$text_color.'">';
$output .= "\n\t" . '<div class="parallax-conteneur"><div class="elementor-background-overlay" style="background-color: '.$color.';"></div>';
$output .= "\n\t\t\t" . js_remove_wpautop($content, true);
$output .= "\n\t\t" . '</div></div> ' . $this->endBlockComment('.wpb_wrapper');
$output .= "\n\t" . $img['thumbnail'].'</div>  <script type="text/javascript">$(".jarallax_'.$img_id.'").jarallax({
  speed: 0.2,
});</script></div>';
$output .= "\n\t" . $this->endBlockComment('.wpb_text_column');

echo $output;