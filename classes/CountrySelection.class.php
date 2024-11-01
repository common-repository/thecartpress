<?php
/**
 * Country selection
 *
 * Outputs a Country selection control
 *
 * @package TheCartPress
 * @subpackage Classes
 * @since 1.3.2
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

if ( ! class_exists( 'TCPCountrySelection' ) ) :
	
class TCPCountrySelection {

	static function init() {
		add_action( 'wp_head', array( __CLASS__, 'wp_head' ), 5 );
	}
	
	static function show() { ?>
		<form method="post" id="tcp_country_selection">
		<div class="tcp_select_country">
			<label for="selected_country_id"><?php _e( 'Country', 'tcp' ); ?></label>
			<?php global $thecartpress;
			$country = isset( $_REQUEST['tcp_selected_country_id'] ) ? $_REQUEST['tcp_selected_country_id'] : false;
			if ( ! $country ) {
				$country = tcp_get_billing_country();
			}
			$billing_isos = $thecartpress->get_setting( 'billing_isos', false );
			if ( $billing_isos ) {
				$countries = TCPCountries::getSome( $billing_isos,  $country );
			} else {
				$countries = TCPCountries::getAll( $country );
			} ?>
			<select id="selected_country_id" name="tcp_selected_country_id">
				<?php foreach( $countries as $item ) : ?>
				<option value="<?php echo $item->iso; ?>" <?php selected( $item->iso, $country ); ?>><?php echo $item->name; ?></option>
				<?php endforeach; ?>
			</select>
			<script>
				jQuery( '#selected_country_id' ).change( function() {
					jQuery( '#tcp_country_selection' ).submit();
				} );
			</script>
		</div>
		</form><?php
	}

	static function wp_head() {
		if ( isset( $_REQUEST['tcp_selected_country_id'] ) ) {
			tcp_set_billing_country( $_REQUEST['tcp_selected_country_id'] );
			tcp_set_shipping_as_billing();
		}
	}
}

TCPCountrySelection::init();

endif; // class_exists check