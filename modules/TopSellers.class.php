<?php
/**
 * Top Sellers
 *
 * Widget to display the top sellers products
 *
 * @package TheCartPress
 * @subpackage Modules
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
 * along with This program. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class TCPTopSellers {

	function __construct() {
		add_action( 'widgets_init'								, array( $this, 'widgets_init' ) );
		add_action( 'tcp_checkout_create_order_insert_detail'	, array( $this, 'tcp_checkout_create_order_insert_detail' ), 10, 4 );
		add_shortcode( 'tcp_total_sales'						, array( $this, 'tcp_total_sales' ) );

		// Custom values widget
		//add_filter( 'tcp_custom_list_widget_args'				, array( $this, 'tcp_custom_list_widget_args' ) );
		add_filter( 'tcp_custom_values_get_other_values'		, array( $this, 'tcp_custom_values_get_other_values' ) );
	}

	function widgets_init() {
		global $thecartpress;
		if ( $thecartpress ) {
			$disable_ecommerce = $thecartpress->get_setting( 'disable_ecommerce', false );
			if ( ! $disable_ecommerce ) {
				require_once( TCP_WIDGETS_FOLDER . 'TopSellersWidget.class.php' );
				register_widget( 'TopSellersWidget' );
			}
		}
	}

	function tcp_checkout_create_order_insert_detail( $order_id, $orders_details_id, $post_id, $ordersDetails ) {
		$n = tcp_get_the_total_sales( $post_id );
		$n += $ordersDetails['qty_ordered'];
		update_post_meta( $post_id, 'tcp_total_sales', $n++ );
	}

	function tcp_total_sales( $atts ) {
		extract( shortcode_atts( array( 'post_id' => 0 ), $atts ) );
		return tcp_get_the_total_sales( $post_id );
	}

	// Displays on Custom values widget
	function tcp_custom_list_widget_args( $loop_args ) {
		$is_saleable = isset( $loop_args['post_type'] ) ? tcp_is_saleable_post_type( $loop_args['post_type'] ) : false;
		if ( $is_saleable ) {
			$args['meta_query'][] = array(
				'key'		=> 'tcp_units_sold',
				'value'		=> 0,
				'type'		=> 'NUMERIC',
				'compare'	=> '!='
			);
			$args = apply_filters( 'tcp_units_sold_custom_list_widget_args', $args );
			$loop_args = array_merge( $loop_args, $args );
		}
		return $loop_args;
	}

	function tcp_custom_values_get_other_values( $other_values ) {
		$other_values['tcp_units_sold'] = array(
			'label'		=> __( 'Units sold', 'tcp' ),
			'callback'	=> 'tcp_get_the_total_sales',
		);
		return $other_values;
	}

}

new TCPTopSellers();

/**
 * Returns the total units sold of a given product
 *
 * @param $post_id, given product identifier. If 0 the current post id will be used.
 * @uses get_the_ID, tcp_get_default_id, OrdersDetails::get_product_total_sales, apply_filters, called 'tcp_get_the_total_sales'.
 */
function tcp_get_the_total_sales( $post_id = 0 ) {
	if ( $post_id == 0 ) {
		$post_id = get_the_ID();
	}
	$post_id = tcp_get_default_id( $post_id );
	require_once( TCP_DAOS_FOLDER . 'OrdersDetails.class.php' );
	$total_sales = OrdersDetails::get_product_total_sales( $post_id );
	return apply_filters( 'tcp_get_the_total_sales', $total_sales, $post_id );
}

/**
 * Outputs the total units sold of a given product
 *
 * @param $post_id, given product identifier. If 0 the current post id will be used.
 * @param $echo, if true (default value) the number of units sold is displayed, if false it's returned
 * @uses tcp_get_total_sales
 */
function tcp_the_total_sales( $post_id = 0, $echo = true ) {
	$sales = tcp_get_total_sales( $post_id );
	if ( $echo ) {
		echo $sales;
	} else {
		return $sales;
	}
}
