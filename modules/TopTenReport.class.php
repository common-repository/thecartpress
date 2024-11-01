<?php
/**
 * Reports
 *
 * Usefull reports
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.	If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'TCPTopTenReport' ) ) :

class TCPTopTenReport {

	function __construct() {
		if ( is_admin() ) {
			
			// Defines 'TopTen Report' page
			add_submenu_page( 'tcp' , __( 'Top Ten Best Sellers', 'tcp' ), __( 'Top Ten Best Sellers', 'tcp' ), 'tcp_edit_products', 'topten-sales-reports', array( $this, 'show' ) );

			// Adds the 'TopTen Reports' tab
			add_filter( 'tcp_reports_tabs'	, array( $this, 'tcp_reports_tabs' ) );
		}
	}

	/**
	 * Adds Top ten report tab
	 *
	 * @param $tabs
	 * @return $tabs
	 */
	function tcp_reports_tabs( $tabs ) {
		$tabs[] = array(
			'title'	=> __( 'Top Ten', 'tcp' ),
			'url'	=> 'admin.php?page=topten-sales-reports',
		);
		return $tabs;
	}

	/**
	 * Returns a list of 10 best sellers products ordered by total amount
	 * 
	 * @param $init_date
	 * @param $end_date
	 * @param $customer_id, -1 is for any customers
	 * @uses filter 'tcp_reports_get_top_ten_products'
	 */
	private function get_top_ten_products( $init_date, $end_date, $customer_id = -1 ) {
		global $wpdb;

		$sql = $wpdb->prepare( 'select od.post_id as ID, sum(od.price * od.qty_ordered) as total_amount, sum(od.qty_ordered) as qty from `' . $wpdb->prefix . 'tcp_orders` o inner join `' . $wpdb->prefix . 'tcp_orders_details` od on (o.order_id = od.order_id) where o.created_at >= %s and o.created_at <= %s', $init_date, $end_date );
		if ( $customer_id > -1 ) {
			$sql .= $wpdb->prepare( ' and o.customer_id = %d', $customer_id );
		}
		$sql .= $wpdb->prepare( ' and o.status = %s', Orders::$ORDER_COMPLETED );
		$sql .= $wpdb->prepare( ' group by od.post_id order by total_amount desc limit %d', 10 );
		
		$sql = apply_filters( 'tcp_reports_get_top_ten_products', $sql, $init_date, $end_date, $customer_id );
		return $wpdb->get_results( $sql );
	}

	/**
	 * Outputs the reports
	 * 
	 * @uses 'get_top_ten_products', 'get_top_ten_products'
	 */
	function show() {
		$today = date( 'Y-m-d' );
		$init_date = isset( $_POST['init_date'] ) ? $_POST['init_date'] : $today;
		$init_date_obj = DateTime::createFromFormat( 'Y-m-d', $init_date );
		$init_date = $init_date_obj ? $init_date_obj->format( 'Y-m-d' ) : $today;

		$tomorrow = new DateTime( 'tomorrow' );
		$tomorrow = $tomorrow->format( 'Y-m-d' );
		$end_date = isset( $_POST['end_date'] ) ? $_POST['end_date'] : $tomorrow;
		$end_date_obj = DateTime::createFromFormat( 'Y-m-d', $end_date );
		$end_date = $end_date_obj ? $end_date_obj->format( 'Y-m-d' ) : $tomorrow;

		if ( current_user_can( 'tcp_edit_orders' ) ) {
			$top_ten_products = $this->get_top_ten_products( $init_date, $end_date );
		} else {
			$current_user = wp_get_current_user();
			$top_ten_products = $this->get_top_ten_products( $init_date, $end_date, $current_user->ID );
		}
?>
<script src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js"></script>

<div class="wrap">

	<?php TCPReports::echoHead( __( 'Top Ten Best Sellers', 'tcp' ) ); ?>

	<div class="clear"></div>
	
	<form method="post">
		<label for="init_date"><?php _e( 'To date', 'tcp' ); ?></label>
		<input type="text" name="init_date" id="init_date" size="10" value="<?php echo $init_date; ?>" class="tcp-date-picker"/>
		
		<label for="end_date"><?php _e( 'From date', 'tcp' ); ?></label>
		<input type="text" name="end_date" id="end_date" size="10" value="<?php echo $end_date; ?>" class="tcp-date-picker"/>
		
		<?php wp_nonce_field( 'tcp_reports_top_ten' ); ?>
		<?php submit_button( __( 'See chart', 'tcp' ), 'primary', 'query-reports_top_ten' ); ?>
	</form>

	<h3><?php printf( __( 'By Amount: %s', 'tcp' ), tcp_get_the_currency() ); ?></h3>
	<canvas id="chart_top_ten_amount" width="800" height="400"></canvas>
	
	<h3><?php _e( 'By Quantity', 'tcp' ); ?></h3>
	<canvas id="chart_top_ten_qty" width="800" height="400"></canvas>

	<script>
	var ctx_top_ten_amount = document.getElementById( 'chart_top_ten_amount' ).getContext( '2d' ),
		data_top_ten_amount = {
		    labels: [<?php foreach( $top_ten_products as $line ) { echo '"' . tcp_get_the_title( $line->ID, 0, 0, false, false ) . '", '; } ?>], // ["January", "February", "March", "April", "May", "June", "July"],
		    datasets: [
		        {
		            label: '<?php printf( __( 'Amount %s', 'tcp' ), tcp_get_the_currency() ); ?>',
		            fillColor: 'rgba( 220, 220, 220, 0.5 )',
		            strokeColor: 'rgba( 220, 220, 220, 0.8 )',
		            highlightFill: 'rgba( 220, 220, 220, 0.75 )',
		            highlightStroke: 'rgba( 220, 220, 220, 1 )',
		            data: [<?php foreach( $top_ten_products as $line ) { echo $line->total_amount . ', '; } ?>],
		        },
			]
		},
		
		ctx_top_ten_qty = document.getElementById( 'chart_top_ten_qty' ).getContext( '2d' ),
		data_top_ten_qty = {
		    labels: [<?php foreach( $top_ten_products as $line ) { echo '"' . tcp_get_the_title( $line->ID, 0, 0, false, false ) . '", '; } ?>], // ["January", "February", "March", "April", "May", "June", "July"],
		    datasets: [
		        {
		            label: '<?php _e( 'Qty', 'tcp' ); ?>',
		            fillColor: 'rgba( 151, 187, 205, 0.5 )',
		            strokeColor: 'rgba( 151, 187, 205, 0.8 )',
		            highlightFill: 'rgba( 151, 187, 205,0.75 )',
		            highlightStroke: 'rgba( 151, 187, 205, 1 )',
		            data: [<?php foreach( $top_ten_products as $line ) { echo $line->qty . ', '; } ?>],
		        }
			]
		};

	new Chart( ctx_top_ten_amount ).Bar( data_top_ten_amount );
	new Chart( ctx_top_ten_qty ).Bar( data_top_ten_qty );
	</script>

	<h3><?php _e( 'Data resume', 'tcp' ); ?></h3>
	<table width="90%">
	<tr>
		<th><?php _e( 'Product', 'tcp' ); ?></th>
		<th><?php _e( 'Total amount', 'tcp' ); ?></th>
		<th><?php _e( 'Qty', 'tcp' ); ?></th>
	</tr>
	<?php foreach( $top_ten_products as $line ) : ?>
	<tr>
		<td><?php echo tcp_get_the_title( $line->ID ); ?></td>
		<td style="text-align: right;"><?php echo tcp_format_the_price( $line->total_amount ); ?></td>
		<td style="text-align: right;"><?php echo tcp_number_format( $line->qty ); ?></td>
	</tr>
	<?php endforeach; ?>
	</table>

</div><!-- .wrap -->
<?php
	}
}

function tcp_activate_topten_report() { 
	new TCPTopTenReport();
}

add_action( 'tcp_admin_menu', 'tcp_activate_topten_report' );

endif; // class_exists check