<?php
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

if ( ! class_exists( 'TCPUpdateVersion' ) ) :

class TCPUpdateVersion {

	function update( $thecartpress ) {
		global $wpdb;

		$version = (float)get_option( 'tcp_version' );
		if ( $version < 118 ) {
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders_details MODIFY COLUMN `name` CHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders_details MODIFY COLUMN `option_1_name` CHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders_details MODIFY COLUMN `option_2_name` CHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
			$wpdb->query( $sql );
			$taxonomies = tcp_get_custom_taxonomies();
			if ( is_array( $taxonomies ) && count( $taxonomies ) > 0 ) {
				$save = false;
				foreach( $taxonomies as $id => $taxonomy ) {
					if ( is_array( $taxonomy['rewrite'] ) ) {
						$taxonomies[$id]['rewrite'] = $taxonomy['rewrite']['slug'];
						$save = true;
					}
				}
				if ( $save ) tcp_set_custom_taxonomies( $taxonomies );
			}
			require_once( TCP_DAOS_FOLDER . 'OrdersCostsMeta.class.php' );
			OrdersCostsMeta::createTable();
			update_option( 'tcp_version', 118 );
			//
			//TODO Deprecated 1.2.8
			//
		}
		if ( $version < 120 ) {
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_addresses MODIFY COLUMN `postcode` CHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
			$wpdb->query( $sql );

			$sql = 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'tcp_orders WHERE field = \'billing_tax_id_number\'';
			if ( ! $wpdb->get_row( $sql ) ) {
				$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders ADD COLUMN `billing_tax_id_number` varchar(15) NOT NULL AFTER `billing_company`;';
				$wpdb->query( $sql );
			}
			//
			//TODO Deprecated 1.3
			//
		}
		if ( $version < 126 ) {
			$sql = 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'tcp_orders WHERE field = \'payment_notice\'';
			$row = $wpdb->get_row( $sql );
			if ( ! $row ) {
				$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders ADD COLUMN `payment_notice` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `payment_method`;';
				$wpdb->query( $sql );
			}
			$sql = 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'tcp_orders WHERE field = \'shipping_notice\'';
			$row = $wpdb->get_row( $sql );
			if ( ! $row ) {
				$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders ADD COLUMN `shipping_notice` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `weight`;';
				$wpdb->query( $sql );
			}
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `comment_internal` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
			$wpdb->query( $sql );
			//
			//TODO Deprecated 1.4
			//
		}
		if ( $version < 127.1 ) {
			$customer = get_role( 'customer' );
			if ( $customer ) $customer->remove_cap( 'tcp_edit_addresses' );
			if ( $customer ) $customer->add_cap( 'tcp_edit_address' );
			$merchant = get_role( 'merchant' );
			if ( $merchant ) $merchant->add_cap( 'tcp_edit_address' );
			$administrator = get_role( 'administrator' );
			if ( $administrator ) $administrator->add_cap( 'tcp_edit_address' );
		}
		if ( $version < 128 ) {
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders_details MODIFY COLUMN `weight` double NOT NULL;';
			$wpdb->query( $sql );
		}
		if ( $version < 129 ) {
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `shipping_firstname` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `shipping_lastname` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `shipping_company` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `shipping_street` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `shipping_city` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `shipping_region` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `shipping_country` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `billing_firstname` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `billing_lastname` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `billing_company` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `billing_street` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `billing_city` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `billing_region` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders MODIFY COLUMN `billing_country` varchar(255) NOT NULL;';
			$wpdb->query( $sql );
			update_option( 'tcp_version', 129 );
		}
		if ( $version < 131 ) {
			$post_type_defs = tcp_get_custom_post_types();
			$post_type_defs['tcp_product']['menu_icon'] = plugins_url( '/images/tcp.png', dirname( __FILE__ ) );
			tcp_update_custom_post_type( 'tcp_product', $post_type_defs['tcp_product'] );
			update_option( 'tcp_version', 131 );
		}
		if ( $version < 132 ) {
			$sql = 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'tcp_orders WHERE field = \'shipping_class\'';
			$row = $wpdb->get_row( $sql );
			if ( ! $row ) {
				$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders ADD COLUMN `shipping_class` VARCHAR(255) NOT NULL AFTER `shipping_method`;';
				$wpdb->query( $sql );
			}
			$sql = 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'tcp_orders WHERE field = \'shipping_instance\'';
			$row = $wpdb->get_row( $sql );
			if ( ! $row ) {
				$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders ADD COLUMN `shipping_instance` INT NOT NULL AFTER `shipping_class`;';
				$wpdb->query( $sql );
			}
			$sql = 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'tcp_orders WHERE field = \'billing_street_2\'';
			$row = $wpdb->get_row( $sql );
			if ( ! $row ) {
				$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders ADD COLUMN `billing_street_2` VARCHAR(255) NOT NULL AFTER `billing_street`;';
				$wpdb->query( $sql );
			}
			$sql = 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'tcp_orders WHERE field = \'shipping_street_2\'';
			$row = $wpdb->get_row( $sql );
			if ( ! $row ) {
				$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders ADD COLUMN `shipping_street_2` VARCHAR(255) NOT NULL AFTER `shipping_street`;';
				$wpdb->query( $sql );
			}
			$sql = 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'tcp_addresses WHERE field = \'street_2\'';
			$row = $wpdb->get_row( $sql );
			if ( ! $row ) {
				$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_addresses ADD COLUMN `street_2` VARCHAR(255) NOT NULL AFTER `street`;';
				$wpdb->query( $sql );
			}
			update_option( 'tcp_version', 132 );
		}
		if ( $version < 135 ) {
			$sql = 'SHOW COLUMNS FROM ' . $wpdb->prefix . 'tcp_orders_details WHERE field = \'discount\'';
			$row = $wpdb->get_row( $sql );
			if ( ! $row ) {
				$sql = 'ALTER TABLE ' . $wpdb->prefix . 'tcp_orders_details ADD COLUMN `discount` DECIMAL(13,2) NOT NULL AFTER `qty_ordered`;';
				$wpdb->query( $sql );
			}
			update_option( 'tcp_version', 135 );
		}
		if ( $version < 139 ) {
			$sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'tcp_countries where iso = \'DZ\'';
			$count = $wpdb->get_col( $sql );
			if ( is_array( $count ) && $count[0] == 0 ) {
				$sql = 'INSERT INTO `' . $wpdb->prefix . 'tcp_countries` VALUES (\'DZ\',\'Algeria\',\'Algeria\',\'Argelia\',\'Algeria\',\'Algérie\',\'DZA\',012,0,0,0);';
				$wpdb->query( $sql );
			}
		}

		if ( $version < 147 ) {
			$sql = 'INSERT INTO `' . $wpdb->prefix . 'tcp_countries` VALUES (\'GG\',\'GUERNSEY\',\'GUERNSEY\',\'GUERNSEY\',\'GUERNSEY\',\'GUERNSEY\',\'GGY\',831,0,0,0);';
			$wpdb->query( $sql );
			
			$sql = 'INSERT INTO `' . $wpdb->prefix . 'tcp_countries` VALUES (\'JE\',\'JERSEY\',\'JERSEY\',\'JERSEY\',\'JERSEY\',\'JAMAÏQUE\',\'JEY\',832,0,0,0);';
			$wpdb->query( $sql );
			
			$sql = 'INSERT INTO `' . $wpdb->prefix . 'tcp_countries` VALUES (\'IM\',\'ISLE OF MAN\',\'ISLE OF MAN\',\'ISLE OF MAN\',\'ISLE OF MAN\',\'L’île de Man\',\'IMN\',833,0,0,0);';
			$wpdb->query( $sql );
		}
		update_option( 'tcp_version', 153 );
	}
}
endif; // class_exists check