<?php
/**
 * ShoppingCart shortcodes
 *
 * Defines two shortcodes for the Shopiing Cart, to show the shopping cart and a button to go to the shopping cart
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

if ( ! class_exists( 'TCPShoppingCartPage' ) ) :

	/**
	 * Shortcode tcp_shopping_cart
	 * Displays the Shopping Cart
	 *
	 * @since 1.2.6
	 */
class TCPShoppingCartPage {

	private function __construct() {}

	static function init() {

		// If visual composer is activated
		add_action( 'vc_before_init', array( __CLASS__, 'vc_define_items' ) );

		add_shortcode( 'tcp_shopping_cart'				, array( __CLASS__, 'show' ) );
		add_shortcode( 'tcp_shopping_cart_button'		, array( __CLASS__, 'show_button' ) );
		add_shortcode( 'tcp_shopping_cart_total_link'	, array( __CLASS__, 'show_total_link' ) );
	}

	static function show( $notice = '' ) {
		$shoppingCart = TheCartPress::getShoppingCart();
		require_once( TCP_CLASSES_FOLDER . 'CartTable.class.php' );
		require_once( TCP_CLASSES_FOLDER . 'CartSourceSession.class.php' );
		ob_start(); ?>
<div class="tcp_shopping_cart_page tcpf">

<?php // Displays Empty Shopping Cart messages
if ( $shoppingCart->isEmpty() ) {
	echo '<span class="tcp_shopping_cart_empty">', __( 'The cart is empty', 'tcp' ), '</span>';

	// If exists one, or more, templates associated
	tcp_do_template( 'tcp_shopping_cart_empty' );

	do_action( 'tcp_shopping_cart_empty' );

// Displaying messages before Shopping Cart
} else { ?>
	<div id="shopping_cart">
		<p class="tcp_shopping_cart_notice">
		<?php if ( is_array( $notice ) && count( $notice ) > 0 ) {
			foreach( $notice as $not ) {
				echo $not, '<br/>';
			}
		} elseif ( strlen( $notice ) > 0 ) {
			echo $notice;
		} ?>
		</p>
	<?php do_action( 'tcp_shopping_cart_before_cart' );

	// Displays Shopping cart
	$cart_table = new TCPCartTable();
	$cart_table->show( new TCPCartSourceSession() );

	do_action( 'tcp_shopping_cart_after_cart' );

	// Displays Continue and Checkout buttons
	$buy_button_color = tcp_get_buy_button_color();
	$buy_button_size = tcp_get_buy_button_size();

	// links at the bottom of the Shopping Cart
	$links = array(
		'tcp_checkout' => array(
			'li_class'	=> 'tcp_sc_checkout',
			'a_class'	=> $buy_button_color . ' ' . $buy_button_size,
			'url'		=> tcp_get_the_checkout_url(),
			'label'		=> __( 'Checkout', 'tcp' )
		),
		'tcp_continue'	=> array(
			'li_class'	=> 'tcp_sc_continue',
			'a_class'	=> 'tcp-btn tcp-btn-default ' . $buy_button_size,
			'url'		=> tcp_get_the_continue_url(),
			'label'		=> __( 'Continue Shopping', 'tcp' )
		),
	);

	$links = apply_filters( 'tcp_shopping_cart_bottom_links', $links ); ?>
		<div class="tcp_sc_links_area">
			<ul class="tcp_sc_links">
			<?php foreach( $links as $link ) { ?>
				<li class="<?php echo $link['li_class']; ?>">
					<button type="submit" onclick="window.location='<?php echo $link['url']; ?>'" class="<?php echo $link['a_class']; ?>"><?php echo $link['label']; ?></button>
				</li>
			<?php } ?>
			</ul>
		</div><!-- .tcp_sc_links_area -->
	</div><!-- #shopping_cart -->
<?php }
	do_action( 'tcp_shopping_cart_footer' ); ?>
</div><!-- .tcp_shopping_cart_page.tcpf -->
<?php do_action( 'tcp_shopping_cart_after' );
		return ob_get_clean();
	}

	/**
	 * Shortcode tcp_shopping_cart_button
	 * Dsplays a button to go to the Shopping Cart
	 *
	 * @since 1.2.6
	 */
	static function show_button() {
		ob_start(); ?>
<div class="tcp-shopping-cart-direct-link">
	<button onclick="window.location='<?php tcp_the_shopping_cart_url(); ?>'" class="tcp-btn <?php tcp_the_buy_button_color(); ?> <?php tcp_the_buy_button_size(); ?>"><?php echo apply_filters( 'tcp_shopping_cart_button_title', __( 'See Your Shopping Cart', 'tcp' ) ); ?></button>
</div>
		<?php return ob_get_clean();
	}

	/**
	 * Shortcode tcp_shopping_cart_total
	 * Dsplays the total of the shopping cart
	 *
	 * @since 1.2.6
	 */
	static function show_total_link() {
		$total = TheCartPress::getShoppingCart()->getTotal();
		$total = tcp_format_the_price( $total);
		ob_start(); ?>
<div class="tcp-shopping-cart-direct-link">
	<a href="<?php tcp_the_shopping_cart_url(); ?>"><span class="glyphicon glyphicon-shopping-cart"></span>&nbsp;<?php echo $total; ?></a>
</div>
		<?php $out = ob_get_clean();
		return apply_filters( 'tcp_shopping_cart_total_link', $out );
	}

	/**
	 * Defines Visual composer components
	 * 
	 * @since 1.5.2
	 */
	static function vc_define_items() {

		// TheCartPress total (+ link to shoppinf cart)
		vc_map( array(
			'name'			=> __( 'TCP Total Shopping cart link', 'tcp' ),
			'description'	=> __( 'Total Shopping cart link', 'tcp' ),
			'base'			=> 'tcp_shopping_cart_total_link',
			'class'			=> '',
			'controls'		=> 'full',
			'icon'			=> plugins_url( 'images/tcp_icon.png', TCP_FILE ),
			'category'		=> __( 'Content', 'tcp' ),
		) );

		// TheCartPress button
		vc_map( array(
			'name'			=> __( 'TCP Shopping cart link', 'tcp' ),
			'description'	=> __( 'Displays a button to visit Shopping cart', 'tcp' ),
			'base'			=> 'tcp_shopping_cart_button',
			'class'			=> '',
			'controls'		=> 'full',
			'icon'			=> plugins_url( 'images/tcp_icon.png', TCP_FILE ),
			'category'		=> __( 'Content', 'tcp' ),
		) );

		// Shopping cart
		vc_map( array(
			'name'			=> __( 'TCP Shopping cart', 'tcp' ),
			'description'	=> __( 'Displays the Shopping cart', 'tcp' ),
			'base'			=> 'tcp_shopping_cart',
			'class'			=> '',
			'controls'		=> 'full',
			'icon'			=> plugins_url( 'images/tcp_icon.png', TCP_FILE ),
			'category'		=> __( 'Content', 'tcp' ),
		) );
	}
}

TCPShoppingCartPage::init();

endif; // class_exists check