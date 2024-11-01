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

if ( ! class_exists( 'TCPReports' ) ) :

class TCPReports {
	
	/**
	 * Adds the reports menu
	 */
	public function add_submenu() {
		$base = thecartpress()->get_base();
		//add_submenu_page( $base	, __( 'Reports', 'tcp' ), __( 'Reports', 'tcp' ), 'tcp_edit_products', TCP_ADMIN_FOLDER . 'Reports.php' );
		add_submenu_page( $base	, __( 'Reports', 'tcp' ), __( 'Reports', 'tcp' ), 'tcp_edit_products', 'tcp-reports', array( $this, 'show' ) );
	}	

	/**
	 * Outputs reports header: title + tabs
	 * 
	 * @param $title
	 * @using filter tcp_reports_tabs
	 */
	public static function echoHead( $title = '' ) {
		
		/**
		 * Allows to know the current tab, comparing the end of the url
		 */
		function endsWith( $haystack, $needle ) {

			// search forward starting from end minus needle length characters
			return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
		}
		
		// if title adds separator (': ')
		if ( '' !== $title ) {
			$title = ': ' . $title;
		}

		// Adds 'Sales' tab
		$tabs = array(
			array(
				'title'	=> __( 'Sales', 'tcp' ),
				'url'	=> 'admin.php?page=tcp-reports',
			),
		);

		// Allows to add more reports
		$tabs = apply_filters( 'tcp_reports_tabs', $tabs ); ?>

		<?php screen_icon( 'tcp-custom-styles' ); ?><h2><?php _e( 'Reports', 'tcp' ); ?><?php echo $title; ?></h2>
		
		<h3 class="nav-tab-wrapper">

		<?php
		// draws tabs
		$current_url = urldecode( $_SERVER['REQUEST_URI'] );

		foreach( $tabs as $tab ) :
			if ( endsWith( $current_url, $tab['url'] ) ) {
				$class = ' nav-tab-active';
			} else {
				$class = '';
			} ?>
			<a class="nav-tab <?php echo $class; ?>" href="<?php echo $tab['url']; ?>"><?php echo $tab['title']; ?></a>
		<?php endforeach; ?>
		</h3>
		
		<div class="clear"></div>
		
		<br/>
<?php
	}

	/**
	 * Returns a list of total amount and qty by date
	 * 
	 * @param $init_date
	 * @param $end_date
	 * @param $interval, d -> days, m -> month
	 * @param $customer_id, -1 is all customers
	 * @uses filter 'tcp_reports_get_total_amount'
	 */
	private function get_total_amount_qty( $init_date, $end_date, $interval = 'd', $customer_id = -1 ) {
		global $wpdb;

		$sql = $wpdb->prepare( 'select created_at, sum(od.price * od.qty_ordered) as total_amount, sum(qty_ordered) as total_qty from `' . $wpdb->prefix . 'tcp_orders` o inner join `' . $wpdb->prefix . 'tcp_orders_details` od on (o.order_id = od.order_id) where o.created_at >= %s and o.created_at <= %s', $init_date, $end_date );
		if ( $customer_id > -1 ) {
			$sql .= $wpdb->prepare( ' and o.customer_id = %d', $customer_id );
		}
		$sql .= $wpdb->prepare( ' and o.status = %s', Orders::$ORDER_COMPLETED );
		if ( $interval == 'd' ) {
			$sql .= ' group by date(created_at)';
		} else if ( $interval == 'm' ) {
			$sql .= ' group by year(created_at), month(created_at)';
		}
		$sql = apply_filters( 'tcp_reports_get_total_amount', $sql, $init_date, $end_date, $interval, $customer_id );
		return $wpdb->get_results( $sql );
	}

	/**
	 * Returns total amount and total qty
	 * 
	 * @param $init_date
	 * @param $end_date
	 * @param $customer_id, -1 is all customers
	 * @uses filter 'tcp_reports_get_totals'
	 */
	private function get_totals( $init_date, $end_date, $customer_id = -1 ) {
		global $wpdb;

		$sql = $wpdb->prepare( 'select sum(od.price * od.qty_ordered) as total_amount, sum(qty_ordered) as total_qty from `' . $wpdb->prefix . 'tcp_orders` o inner join `' . $wpdb->prefix . 'tcp_orders_details` od on (o.order_id = od.order_id) where o.created_at >= %s and o.created_at <= %s', $init_date, $end_date );
		if ( $customer_id > -1 ) {
			$sql .= $wpdb->prepare( ' and o.customer_id = %d', $customer_id );
		}
		$sql .= $wpdb->prepare( ' and o.status = %s', Orders::$ORDER_COMPLETED );
		$sql = apply_filters( 'tcp_reports_get_totals', $sql, $init_date, $end_date, $customer_id );
		return $wpdb->get_row( $sql );
	}

	/**
	 * Outputs the reports
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

		// if the query covers more than 31 days it will be displayed monthly
		$diff = $init_date_obj->diff( $end_date_obj );
		$interval = $diff->days > 31 ? 'm' : 'd';

		if ( current_user_can( 'tcp_edit_orders' ) ) {

			// Query for all orders
			$totals_db = $this->get_total_amount_qty( $init_date, $end_date, $interval );
			$total = $this->get_totals( $init_date, $end_date );
		} else {
			
			// Query only for user's orders
			$current_user = wp_get_current_user();
			$totals_db = $this->get_total_amount_qty( $init_date, $end_date, $interval, $current_user->ID );
			$total = $this->get_totals( $init_date, $end_date, $current_user->ID );
		}

		$totals = array();

		// Creates the real array, day by day or month by monthm, betwen dates
		if ( is_array( $totals_db ) && count( $totals_db ) > 0 ) {
			if ( $interval == 'd' ) {
				// diary
				$j = 0;
				$obj = new DateTime( $totals_db[$j]->created_at );
				$totals_db[$j]->created_at = $obj->format('Y-m-d');
				for( $i = 0; $i <= $diff->days; $i++ ) {
					$today = date( 'Y-m-d', strtotime( $init_date . ' +' . $i . ' day' ) );
					if ( isset( $totals_db[$j] ) && $today == $totals_db[$j]->created_at ) {
						$totals[] = $totals_db[$j];
						$j++;
						if ( isset(  $totals_db[$j] ) ) {
							$obj = new DateTime( $totals_db[$j]->created_at );
							$totals_db[$j]->created_at = $obj->format('Y-m-d');
						}
					} else {
						$totals[]= (object)array(
							'created_at'	=> $today,
							'total_amount'	=> 0,
							'total_qty'		=> 0,
						);
					}
				}
			} else {
				// Monthly
			}
		}
		$total_amount = $total->total_amount;
		$total_qty = $total->total_qty;
?>

<style>
#total-box {
	background: #E8E8E8;
	padding: 1em;
	margin: 1em;
	float: left;
	display: box;
}

#total-box .title,
#total-box .link-to-chart{
	text-align: right;
	text-decoration: none;
}

#total-box .value {
	font-size: 2em;
	padding-top:1em;
}

#total-box .dashicons {
	color: #0099FF;
	float: left;
}
</style>
<script src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js"></script>

<div class="wrap">

	<?php TCPReports::echoHead( __( 'Sales', 'tcp' ) ); ?>

	<form method="post">
		<label for="init_date"><?php _e( 'Search from', 'tcp' ); ?></label>
		<input type="text" name="init_date" id="init_date" value="<?php echo $init_date; ?>" class="tcp-date-picker" size="10" />
		
		<label for="end_date"><?php _e( 'to', 'tcp' ); ?></label>
		<input type="text" name="end_date" id="end_date" value="<?php echo $end_date; ?>" class="tcp-date-picker" size="10" />
		
		<?php wp_nonce_field( 'tcp_reports_top_ten' ); ?>
		<?php submit_button( __( 'See Charts', 'tcp' ), 'primary', 'query-reports_top_ten' ); ?>
	</form>

	<div id="total-boxes">
		<div id="total-box" class="total-amount">
			<span class="dashicons dashicons-cart"></span><div class="title"><?php _e( 'Total Amount', 'tcp' ); ?></div>
			<div class="link-to-chart"><a href="#total-amount-area"><?php _e( 'See chart', 'tcp' ); ?></a></div>
			<div class="value"><?php echo tcp_format_the_price( $total_amount ); ?></div>
		</div>
	
		<div id="total-box" class="total-qty">
			<span class="dashicons dashicons-products"></span><div class="title"><?php _e( 'Total Quantity', 'tcp' ); ?></div>
			<div class="link-to-chart"><a href="#total-qty-area"><?php _e( 'See chart', 'tcp' ); ?></a></div>
			<div class="value"><?php printf( __( '%s units', 'tcp' ), tcp_format_number( $total_qty ) ); ?></div>
		</div>
	</div>
	<div class="clear"></div>

	<h3 id="total-amount-area"><?php _e( 'Total amount', 'tcp' ); ?></h3>

	<canvas id="chart_total_amount" width="800" height="400"></canvas>

	<h3 id="total-qty-area"><?php _e( 'Total Quantity', 'tcp' ); ?></h3>

	<canvas id="chart_total_qty" width="800" height="400"></canvas>

	<script>
	var ctx_total_amount = document.getElementById( 'chart_total_amount' ).getContext( '2d' ),
		data_total_amount = {
		    labels: [<?php foreach( $totals as $line ) { $dt = new DateTime( $line->created_at );
				echo '"' . $dt->format( 'Y-m-d' ) . '", '; } ?>],
		    // ["January", "February", "March", "April", "May", "June", "July", "Agoust", "September", "October", "November", "December"],
		    datasets: [
		        {
		            label: '<?php printf( __( 'Total amount %s', 'tcp' ), tcp_get_the_currency() ); ?>',
		            fillColor: 'rgba( 220, 220, 220, 0.5 )',
		            strokeColor: 'rgba( 220, 220, 220, 0.8 )',
		            highlightFill: 'rgba( 220, 220, 220, 0.75 )',
		            highlightStroke: 'rgba( 220, 220, 220, 1 )',
		            data: [<?php foreach( $totals as $line ) { echo $line->total_amount . ', '; } ?>],
		        },
			]
		},

		ctx_total_qty = document.getElementById( 'chart_total_qty' ).getContext( '2d' ),
		data_total_qty = {
		    labels: [<?php foreach( $totals as $line ) { $dt = new DateTime( $line->created_at );
				echo '"' . $dt->format( 'Y-m-d' ) . '", '; } ?>],
		    datasets: [
		        {
		            label: '<?php _e( 'Qty', 'tcp' ); ?>',
		            fillColor: 'rgba( 151, 187, 205, 0.5 )',
		            strokeColor: 'rgba( 151, 187, 205, 0.8 )',
		            highlightFill: 'rgba( 151, 187, 205,0.75 )',
		            highlightStroke: 'rgba( 151, 187, 205, 1 )',
		            data: [<?php foreach( $totals as $line ) { echo $line->total_qty . ', '; } ?>],
		        }
			]
		};

	new Chart( ctx_total_amount ).Bar( data_total_amount );
	new Chart( ctx_total_qty ).Bar( data_total_qty );
	</script>

	<h3><?php _e( 'Data resume', 'tcp' ); ?></h3>
	<table width="90%">
	<tr>
		<th><?php _e( 'Created at', 'tcp' ); ?></th>
		<th><?php _e( 'Total amount', 'tcp' ); ?></th>
		<th><?php _e( 'Qty', 'tcp' ); ?></th>
	</tr>
	<?php if ( is_array( $totals ) && count( $totals ) > 0 ) {
		foreach( $totals as $line ) { ?>
	<tr>
		<td><?php $dt = new DateTime( $line->created_at );
		echo $dt->format( 'Y-m-d' ); ?></td>
		<td style="text-align: right;"><?php echo tcp_format_the_price( $line->total_amount ); ?></td>
		<td style="text-align: right;"><?php echo tcp_number_format( $line->total_qty ); ?></td>
	</tr>
	<?php }
	} ?>
	</table>

</div><!-- .wrap -->

<script type="text/javascript">

/*jQuery( document ).ready( function() {
	jQuery( 'input[type="date"]' ).datepicker( {
		dateFormat : 'dd-mm-yy'
	} );
} );*/
</script>
<?php
	}
}

add_action( 'tcp_admin_menu', array( new TCPReports(), 'add_submenu' ) );

endif; // class_exists check