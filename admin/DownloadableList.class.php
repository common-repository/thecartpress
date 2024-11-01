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

if ( ! class_exists( 'TCPDownloadableList' ) ) :

require_once( TCP_DAOS_FOLDER . 'Orders.class.php' );

class TCPDownloadableList {
	function show( $echo = true ) {
		ob_start(); ?>
<script language="JavaScript">
function tcp_refresh( order_detail_id ) {
	//$path = 'admin.php?page=' . plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/admin/VirtualProductDownloader.php';
	//url = '<?php echo WP_PLUGIN_URL, '/', plugin_basename( dirname( __FILE__ ) ), '/VirtualProductDownloader.php'; ?>';
	url = '<?php echo plugins_url(), '/', plugin_basename( dirname( __FILE__ ) ); ?>/VirtualProductDownloader.php';
	url = url + '?order_detail_id=' + order_detail_id;
	setTimeout( 'tcp_reload()', 3000 );
	window.open( url, 'downloadable' );
}

function tcp_reload() {
	window.location.reload( false );
}
</script>
<div class="wrap">

<?php if ( is_admin() ) : ?>
	<?php screen_icon( 'tcp-download-list' ); ?><h2><?php echo __( 'Downloadable products', 'tcp' ); ?></h2>
	<div class="clear"></div>
<?php endif;

if ( ! is_user_logged_in() ) :
	ob_start();
?>

	<p><?php _e( 'You need to login to see your downloads.', 'tcp-fe' ); ?></p>
	<?php tcp_login_form( array( 'echo' => true ) );

	$out = ob_get_clean();
	if ( $echo ) {
		echo $out;
	} else {
		return $out;
	}
endif;

$current_user = wp_get_current_user();
$orders = Orders::getProductsDownloadables( $current_user->ID );
if ( is_array( $orders ) && count( $orders ) > 0 ) {
	$max_date = date( 'Y-m-d', mktime( 0, 0, 0, 1, 1, 2000 ) ); ?>
	<table class="tcp_my_downloads wp-list-table widefat fixed striped posts">
	<tbody>
	<?php foreach( $orders as $order ) : ?>
		<tr class="alternate">
		<td class="tcp_thumbnail" style="width: 120px">
			<?php echo get_the_post_thumbnail( $order->post_id, array( 100, 100 ) );?>
		</td>
		<td class="tcp_title" valign="top">
			<h2><a href="<?php echo get_permalink( $order->post_id ); ?>"><?php echo get_the_title( $order->post_id ); ?></a></h2>
			<p class="tcp_expires_at">
				<?php if ( $order->expires_at != $max_date ) {
					printf( __( 'Expires at %s', 'tcp' ), $order->expires_at );
				} else {
					_e( 'This download doesn\'t expire', 'tcp' );
				} ?>
			</p>

			<?php if ( $order->max_downloads > -1 ) { ?>
			<p class="tcp_expires_close">
			<?php printf( __( 'Remaining number of downloads are %s', 'tcp' ), $order->max_downloads ); ?>
			</p>
			<?php } ?>
		</td>
		<td class="tcp_download tcp_expires">
			<button onclick="tcp_refresh( <?php echo $order->order_detail_id; ?> ); return false;" title="<?php _e( 'download the product', 'tcp' );?>" class="button button-primary"><?php _e( 'Download', 'tcp' ); ?></button>
		</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
	</table>
<?php } else  {
	_e( 'No products to download', 'tcp' );
}?>
</div>
<?php
		$out = ob_get_clean();
		if ( $echo ) {
			echo $out;
		} else {
			return $out;
		}
	}
}
endif; // class_exists check