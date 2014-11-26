<?php if (!defined('FW')) die('Forbidden');

class FW_Shortcode_Slider extends FW_Shortcode
{
	protected function _render($atts, $content = null, $tag = '')
	{
		return fw()->extensions->get('slider')->render_slider($atts['slider_id'],
			array(
				'width' => $atts['width'],
				'height' => $atts['height']
			)
		);
	}
}