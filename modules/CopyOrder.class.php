<?php
/**
 * Copy Order
 *
 * Adds a copy to Shopping cart button to the FrontEnd module.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'TCPCopyOrder' ) ) :

/**
 * Allows to copy orders from created ones
 * An Order can be copied if we are the owber or if it's set as public.
 */
class TCPCopyOrder {

	/**
	 * Initialices the class
	 */
	static function initModule() {
		add_action( 'tcp_init'							, array( __CLASS__, 'tcp_init' ), 0 );

		// Front End support
		add_filter( 'tcp_front_end_orders_columns'		, array( __CLASS__, 'tcp_front_end_orders_columns' ) );
		add_action( 'tcp_front_end_orders_cells'		, array( __CLASS__, 'tcp_front_end_orders_cells' ) );
		add_action( 'tcp_front_end_orders_order_view'	, array( __CLASS__, 'tcp_front_end_orders_cells' ) );
		if ( is_admin() ) {
			add_action( 'tcp_admin_order_submit_area'	, array( __CLASS__, 'tcp_admin_order_submit_area' ) );

			// Adds the "public to copy" field in the order management
			add_action( 'tcp_admin_init'					, array( __CLASS__, 'tcp_admin_init' ) );
		}

		// Adds a shortcode to outputs a button to copy an order
		add_shortcode( 'tcp_copy_order_button', array( __CLASS__, 'tcp_copy_order_button_shortcode' ) );
	}

	static function tcp_admin_init() {
		add_action( 'tcp_admin_order_before_editor'	, array( __CLASS__, 'tcp_admin_order_before_editor' ), 10, 2 );
		add_action( 'tcp_admin_order_editor_save'	, array( __CLASS__, 'tcp_admin_order_editor_save' ) );
	}

	static function tcp_init() {
		if ( isset( $_REQUEST['tcp_copy_order_to_shopping_cart'] ) ) {
			$order_id = isset( $_REQUEST['tcp_copy_order_order_id'] ) ? $_REQUEST['tcp_copy_order_order_id'] : 0;

			// If the order is set to public to copy
			if ( tcp_get_order_meta( $order_id, 'public_to_copy' ) == true ) {
				TCPCopyOrder::copyOrder( $order_id );
				wp_redirect( tcp_get_the_shopping_cart_url() );
				exit();
			} else {
				//If the current user is the owner of the order to copy
				$current_user = wp_get_current_user();
				require_once( TCP_DAOS_FOLDER . 'Orders.class.php' );
				if ( Orders::is_owner( $order_id, $current_user->ID ) ) {
					TCPCopyOrder::copyOrder( $order_id );
					wp_redirect( tcp_get_the_shopping_cart_url() );
					exit();
				}
			}
		}
	}

	/**
	 * Copies one order, saved in the database, in the shopping cart
	 *
	 *@param $order_id, order to copy
	 */
	static function copyOrder( $order_id ) {
		$shoppingCart = TheCartPress::getShoppingCart();
		$shoppingCart->deleteAll();
		$details = OrdersDetails::getDetails( $order_id );
		foreach( $details as $detail ) {
			$unit_price = tcp_get_the_product_price( $detail->post_id );
			//$unit_price = tcp_get_the_price( $detail->post_id, false );
			$unit_weight = tcp_get_the_weight( $detail->post_id );
			if ( $detail->option_1_id > 0 ) {
				$unit_price += tcp_get_the_price( $detail->option_1_id );
			}
			if ( $detail->option_2_id > 0 ) {
				$unit_price += tcp_get_the_price( $detail->option_2_id );
			}
			$shoppingCart->add( $detail->post_id, $detail->option_1_id, $detail->option_2_id, $detail->qty_ordered, $unit_price, $unit_weight );
		}
		do_action( 'tcp_add_shopping_cart' );
	}

	/**
	 * Outputs the Copy button Order managemnt to set or not an order as public to copy
	 *
	 * @param $order_id, current order id
	 */
	static function tcp_admin_order_submit_area( $order_id ) { ?>
<input type="hidden" name="tcp_copy_order_order_id" value="<?php echo $order_id; ?>" />
<button name="tcp_copy_order_to_shopping_cart" type="submit" class="btn btn-success"><?php _e( 'Copy to Shopping Cart', 'tcp' ); ?></button>
	<?php }

	static function tcp_front_end_orders_columns( $cols ) {
		$cols[] = __( 'Actions', 'tcp' );
		return $cols;
	}

	static function tcp_front_end_orders_cells( $order_id ) { ?>
<td class="tcp_copy_order">
	<form method="post" action="<?php tcp_the_shopping_cart_url(); ?>">
	<?php TCPCopyOrder::tcp_admin_order_submit_area( $order_id ); ?>
	</form>
	<?php do_action( 'tcp_copy_from_front_end_orders_cells', $order_id ); ?>
</td>
<?php }

	/**
	 * Outputs the checkout control to set an order public to copy
	 *
	 * @param $order_id, current order id
	 * @param $order, current order
	 */
	static function tcp_admin_order_before_editor( $order_id, $order ) {
		$public_to_copy = tcp_get_order_meta( $order_id, 'public_to_copy' );
		?>
<tr valign="top">
	<th scope="col">
		<label style="font-weight:bold;"><?php _e( 'Public (to copy)', 'tcp' ); ?></label>
	</th>
	<td>
		<input type="checkbox" name="tcp_public_to_copy" <?php checked( $public_to_copy ); ?> value="yes"/>
	</td>
</tr><?php
	}

	/**
	 * Saves the field "public to copy"
	 *
	 * @param $order_id, current order id
	 */
	static function tcp_admin_order_editor_save( $order_id ) {
		tcp_update_order_meta( $order_id, 'public_to_copy', isset( $_REQUEST['tcp_public_to_copy'] ) );
	}

	static function tcp_copy_order_button( $order_id ) { ?>
		<form method="post">
		<input type="hidden" value="<?php echo $order_id; ?>" name="tcp_copy_order_order_id" />
		<button name="tcp_copy_order_to_shopping_cart" type="submit"><?php _e( 'Copy to Shopping Cart', 'tcp' ); ?></button>
		</form><?php
	}

	static function tcp_copy_order_button_shortcode( $atts ) {
		extract( shortcode_atts( array( 'order_id' => '0' ), $atts ) );
		ob_start();
		TCPCopyOrder::tcp_copy_order_button( $order_id );
		return ob_get_clean();
	}
}

TCPCopyOrder::initModule();

/**
 * Template function to output a Copy order button
 *
 * @param $order_id
 */
function tcp_copy_order_button( $order_id ) {
	TCPCopyOrder::tcp_copy_order_button( $order_id );
}

endif; // class_exists check