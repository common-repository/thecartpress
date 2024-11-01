<?php
/**
 * TheCartPress Shortcodes
 *
 * Outputs a shortcode generated
 *
 * @package TheCartPress
 * @subpackage Shortcodes
 */

/**
 * This file is part of TheCartPress.
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'TCPShortcode' ) ) :

class TCPShortcode {

	private function __construct() {}
	
	static function init() {

		// If visual composer is activated
		add_action( 'vc_before_init', array( __CLASS__, 'vc_define_items' ) );

		add_shortcode( 'tcp_list', array( __CLASS__, 'show' ) );
	}

	static function show( $atts ) {
		extract( shortcode_atts( array( 'id' => 'none' ), $atts ) );
		$shortcodes_data = get_option( 'tcp_shortcodes_data' );
		foreach( $shortcodes_data as $shortcode_data ) {
			if ( $shortcode_data['id'] == $id ) {
				$customPostTypeListWidget = new CustomPostTypeListWidget();
				$args = array(
					'before_widget'	=> '<div id="tcp_shortcode_' . $id . '" class="tcp_shortcode tcp_' . $id . '">',
					'after_widget'	=> '</div>',
					'before_title'	=> '',
					'after_title'	=> '',
					'widget_id'		=> $id,
				);
				ob_start();
				// $filter = new TCPFilterNavigation();
				// $shortcode_data['order_type'] = $filter->get_order_type();
				// $shortcode_data['order_desc'] = $filter->get_order_desc();
				$shortcode_data['see_sorting_panel'] = isset( $shortcode_data['see_order_panel'] ) ? $shortcode_data['see_order_panel'] : false;
				$customPostTypeListWidget->widget( $args, $shortcode_data );
				return ob_get_clean();
			}
		}
		return sprintf( __( 'Mal formed shortcode: %s', 'tcp' ), $id );
	}
	
	static function vc_define_items() {
		vc_map( array(
			'name'			=> __( 'TCP Shortcode', 'tcp' ),
			'description'	=> __( 'Display generated shortcodes', 'tcp' ),
			'base'			=> 'tcp_list',
			'class'			=> '',
			'controls'		=> 'full',
			'icon'			=> plugins_url( 'images/tcp_icon.png', TCP_FILE ),
			'category'		=> __( 'Content', 'tcp' ),
			'params'		=> array(
				array(
					'type'			=> 'textfield',
					'holder'		=> 'div',
					'class'			=> '',
					'heading'		=> __( 'Shortcode ID', 'tcp' ),
					'param_name'	=> 'id',
					//'value'			=> '',
					'description'	=> __( 'Id of any generated shortcode.', 'tcp' ),
				),
			)
		) );
	}
}

TCPShortcode::init();

endif; // class_exists check