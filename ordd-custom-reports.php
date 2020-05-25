<?php
/**
 * Plugin Name: Order Delivery Date - Custom Reports
 * Plugin URI: https://www.tychesoftwares.com/
 * Description: Create Custom Reports for number of products to be delivered daily.
 * Version: 1.0.0
 * Author: Tyche Softwares
 * Author URI: https://www.tychesoftwares.com/
 * WC tested up to: 4.1.0
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package ordd-custom-reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'orddd_before_export_actions', 'ordd_add_custom_button' );

function ordd_add_custom_button() {
	?>

	<a
		onclick="javascript:addCustomURL(this);"
		href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'download' => 'orddd_data.print',
					'report'   => 'products',
				)
			)
		);
		?>
				"
		target="_blank"
		class="button-secondary orddd-tooltip"
		style="float:right;margin:5px;"
		id="orddd_print_custom_orders">
			<?php _e( 'Print Products Report', 'order-delivery-date' ); ?>
			<span class="orddd-tooltiptext"><?php _e( 'Print product report for the range selected.', 'order-delivery-date' ); ?></span>
	</a>

	<script type="text/javascript">
		function addCustomURL( element ) {
			jQuery( element ).attr( 'href', function() {
				var start_date = jQuery( '#calendar' ).fullCalendar( 'getView' ).intervalStart.format( 'YYYY-MM-DD' );

				var end_date_obj = jQuery( '#calendar' ).fullCalendar( 'getView' ).intervalEnd;
				var end_date = moment( end_date_obj ).subtract( '1', 'days' ).format( 'YYYY-MM-DD' );

				var orddd_this_href = this.href;
				if ( orddd_this_href.includes( "orddd_data.print" ) ) {
					orddd_this_href = "<?php echo get_admin_url(); ?>/admin.php?page=orddd_view_orders&download=orddd_data.print&report=products";
				} else {
					orddd_this_href = "<?php echo get_admin_url(); ?>/admin.php?page=orddd_view_orders&download=orddd_data.csv&report=products";
				}

				return orddd_this_href + '&eventType=order&orderType=' + jQuery( ".orddd_filter_by_order_status" ).val() +'&start=' + start_date + "&end=" + end_date;
			});
		}
	</script>

	<?php
}

add_filter( 'orddd_print_columns', 'orddd_print_columns' );

function orddd_print_columns( $columns ) {
	if ( isset( $_GET['report'] ) && 'products' === $_GET['report'] ) {
		$columns = "
			<tr>
			<th style='border:1px solid black;padding:5px;'>" . __( 'Product Name', 'order-delivery-date' ) . "</th>
			<th style='border:1px solid black;padding:5px;'>" . __( 'Total to prepare and deliver', 'order-delivery-date' ) . '</th>
			</tr>';
	}
	return $columns;
}

add_filter( 'orddd_print_rows', 'orddd_print_rows', 10, 2 );

function orddd_print_rows( $rows, $data ) {
	if ( isset( $_GET['report'] ) && 'products' === $_GET['report'] ) {
		$rows     = '';
		$data_map = array();

		foreach ( $data as $key => $value ) {

			$order = wc_get_order( $value->order_id );
			foreach ( $order->get_items() as $item_id => $item ) {
				$product  = $item->get_product();
				if ( 'simple' !== $product->get_type() ) {
					$product = wc_get_product( $product->get_parent_id() );
				}
				$prod_cat = $product->get_category_ids();

				$prod_cat_name = ( count( $prod_cat ) > 0 ) ? ordd_get_product_category_by_id( $prod_cat[0] ) : 'Uncategorized';

				$prod_name = $item->get_name();
				$prod_qty  = $item->get_quantity();

				if ( ! isset( $data_map[ $prod_cat_name ] ) ) {
					$data_map[ $prod_cat_name ] = array();
				}

				$data_map[ $prod_cat_name ][ $prod_name ] = isset( $data_map[ $prod_cat_name ][ $prod_name ] ) ? $data_map[ $prod_cat_name ][ $prod_name ] + $prod_qty : $prod_qty;
			}
		}
		ksort( $data_map );
		foreach ( $data_map as $pid => $pvalue ) {
			$rows .= "<tr>
				<td style='border:1px solid black;padding:5px;background:black;color:white;text-align:center;' colspan=2>Category: " . $pid . '</td>
				</tr>';

			ksort( $pvalue );

			foreach ( $pvalue as $pname => $pqty ) {
				$rows .= "<tr>
					<td style='border:1px solid black;padding:5px;'>" . $pname . "</td>
					<td style='border:1px solid black;padding:5px;'>" . $pqty . '</td></tr>';
			}
		}
	}
	return $rows;
}

/**
 * Get category name by ID
 *
 * @param int $category_id Category ID.
 *
 * @return string
 */
function ordd_get_product_category_by_id( $category_id ) {
	$term = get_term_by( 'id', $category_id, 'product_cat', 'ARRAY_A' );
	return $term['name'];
}
