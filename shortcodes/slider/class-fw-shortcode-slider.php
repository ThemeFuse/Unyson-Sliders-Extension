<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

class FW_Shortcode_Slider extends FW_Shortcode {
	protected function _render( $atts, $content = null, $tag = '' ) {
		if ( ! empty( $atts['slider_id'] ) ) {
			return fw()->extensions->get( 'slider' )->render_slider( $atts['slider_id'],
				array(
					'width'  => empty( $atts['width'] ) ? 300 : $atts['width'],
					'height' => empty( $atts['height'] ) ? 200 : $atts['height'],
				), apply_filters( 'fw_slider_add_shortcode_extra_data', array(), $atts ) );
		}
	}
}