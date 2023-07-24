<?php
/**
 * Template for invoice content
 *
 * Override this template by copying it to yourtheme/woocommerce-invoice-pdf/invoice-content.php
 *
 * @version     0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//////////////////////////////////////////////////
// init
//////////////////////////////////////////////////

do_action( 'wp_wc_invoice_pdf_start_template', $args );

$refund 	= $args[ 'refund' ];
$order		= $args[ 'order' ];
$test		= false;

$wc_mails 	= WC_Emails::instance(); // load all actions
$is_test 	= is_string( $args[ 'order' ] ) && $args[ 'order' ] == 'test';
$dummy_mail = new WP_WC_Invoice_Pdf_Dummy_Mail();

if ( ! $is_test ) {
	$refund_id				= $refund->get_id();
	$order_number			= $order->get_order_number();
	$order_date				= $order->get_date_created()->format( 'Y-m-d' );
	$billing_address		= $order->get_formatted_billing_address();
	$items					= $order->get_items();
	$first_name				= $order->get_billing_first_name();
	$last_name				= $order->get_billing_last_name();
	$shipping_address 		= $order->get_formatted_shipping_address();
} else { // test pdf
	$test 					= true;
	$order_number			= rand( 1000, 99999 );
	$order_date				= date( 'Y-m-d' );
	$billing_address		= __( 'John', 'woocommerce-german-market' ) . ' ' . __( 'Doe', 'woocommerce-german-market' ) . '<br/>' . __( '42 Example Avenue', 'woocommerce-german-market' ) . '<br/>' . __( 'Springfield, IL 61109', 'woocommerce-german-market' );
	$shipping_address		= __( 'Marry', 'woocommerce-german-market' ) . ' ' . __( 'Doe', 'woocommerce-german-market' ) . '<br/>' . __( '71 Example Street', 'woocommerce-german-market' ) . '<br/>' . __( 'Denver, IL 61109', 'woocommerce-german-market' );
	$first_name				= __( 'John', 'woocommerce-german-market' );
	$last_name				= __( 'Doe', 'woocommerce-german-market' );
	// we need an existing order to run the actions ( e.g. woocommerce_email_after_order_table )
	$example_order 			= get_option( 'wp_wc_invoice_pdf_example_order', '' );
	$need_new_example_order	= true;
	if ( $example_order != '' ) {
		$example_order_array	= explode( '_', $example_order );
		if ( $example_order_array[ 1 ] > time() - 60*60*24 ) { // after 1 day, look for another example order
			$order_id		= $example_order_array[ 0 ];
			$order 			= wc_get_order( $order_id );
			$order_status	= str_replace( 'wc-', '', $order->get_status() );
			if ( in_array( $order_status, array( 'completed', 'processing', 'pending', 'on-hold' ) ) ) {
				$items = $order->get_items();
				$need_new_example_order = false;
				$there_is_an_example = true;
			}
		}
	}
	if ( $need_new_example_order ) {
		$args = array(
			'post_type'			=> 'shop_order',
			'post_status' 		=> array( 'wc-completed', 'wc-processing', 'wc-pending', 'wc-on-hold' ),
			'posts_per_page'	=> 10,
			'orderby'			=> 'post_date',
			'order'				=> 'DESC'
		);
		$orders = get_posts( $args );
		foreach ( $orders as $post_example ) {
			// do not use orders with no item (created manualle)
			$order_example = wc_get_order( $post_example );
			if ( count( $order_example->get_items() ) > 0 ) {
				$order = wc_get_order( $order_example );
				$there_is_an_example = true;
				$items = $order->get_items();
				update_option( 'wp_wc_invoice_pdf_example_order', $order->get_id() . '_' . time() );
				break;
			}
		}
	}
}

$can_use_order = ( ! $test ) || ( $test && $there_is_an_example );
if ( $can_use_order ) {
	$order_date_formated	= sprintf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order_date ) ), date_i18n( wc_date_format(), strtotime( $order_date ) ) );
} else {
	$order_date_formated	= __( 'Ex Date', 'woocommerce-german-market' );
}
$content			= '';
$cell_padding		= get_option( 'wp_wc_invoice_pdf_table_cell_padding', 5 );
$show_pos 			= get_option( 'wp_wc_invoice_pdf_show_pos_in_invoice', false );
$show_sku			= get_option( 'wp_wc_invoice_pdf_show_sku_in_invoice', true );
$show_weight		= get_option( 'wp_wc_invoice_pdf_show_weight_in_invoice', false );
$show_dimensions	= get_option( 'wp_wc_invoice_pdf_show_dimensions_in_invoice', false );
$show_product_image = get_option( 'wp_wc_invoice_pdf_show_product_image_in_invoice_pdf', false );
$product_image_width= get_option( 'wp_wc_invoice_pdf_product_image_width', 75 );
$show_purchase_note	= get_option( 'wp_wc_invoice_pdf_show_purchase_note_in_invoice', false );
$show_short_desc	= get_option( 'wp_wc_invoice_pdf_show_short_description_in_invoice', false );

$wc_price_args = array();

if ( WGM_Helper::method_exists( $order, 'get_currency' ) ) {
	$wc_price_args[ 'currency' ] = $order->get_currency();
}

//////////////////////////////////////////////////
// billing address
//////////////////////////////////////////////////
$additoinal_notation = get_option( 'wp_wc_invoice_pdf_billing_address_additional_notation', get_bloginfo( 'name' ) );
?>
<div class="helper-billing-address">
	<table class="billing-address" cellspacing="0" border="0">
	   <?php if ( trim( $additoinal_notation ) != '{{blank}}' ) { ?>
		<tr>
			<?php $additoinal_notation = strip_tags( $additoinal_notation, '<i><strong><u><b>' ); ?>
            <td class="additional-notation"><?php echo nl2br( $additoinal_notation ); ?></td>
		</tr>
		<?php } ?>
		<tr>
			<td class="address">
				<?php
				if ( apply_filters( 'wp_wc_invoice_pdf_use_shipping_adress_as_billing_adress', false, $order ) ) {
					echo $shipping_address;
				} else {
					echo apply_filters( 'wp_wc_invoice_pdf_output_billing_adress', $billing_address, $order );
				}
				?>
			</td>
		</tr>
	</table>
</div>
<?php

//////////////////////////////////////////////////
// subject
//////////////////////////////////////////////////

$subject_lines = array(
	'wp_wc_invoice_pdf_refund_start_subject_big' => __( 'Refund {{refund-id}}', 'woocommerce-german-market' ),
	'wp_wc_invoice_pdf_refund_start_subject_small' => __( 'For order {{order-number}}', 'woocommerce-german-market' )
);


$subject_placeholders = apply_filters( 'wp_wc_invoice_pdf_placeholders', array( 'refund-id' => __( 'Refund ID', 'woocommerce-german-market' ), 'order-number' => __( 'Order Number', 'woocommerce-german-market' ) ) );

foreach ( $subject_lines as $subject_line_key => $subject_line ) {

	$subject_lines[ $subject_line_key ] = get_option( $subject_line_key, $subject_line );
	$search = array();
	$replace = array();
	foreach( $subject_placeholders as $placeholder_key => $placeholder_value ) {
		$search[] = '{{' . $placeholder_key . '}}';
		if ( $placeholder_key == 'order-number' ) {
			$replace[] = $order_number;
		} else if ( $placeholder_key == 'refund-id' ) {
			$replace[] = $refund_id;
		} else {
			$replace[] = apply_filters( 'wp_wc_invoice_pdf_placeholder_' . $placeholder_key, $placeholder_value, $placeholder_key, $order );
		}
	}

	$subject_lines[ $subject_line_key ] = str_replace( $search, $replace , $subject_lines[ $subject_line_key ] );
}

?>
<table class="subject" cellspacing="0" cellpadding="0" border="0">
	<tr>
        <?php
        $refund_date = date_i18n( get_option( 'date_format' ), $refund->get_date_created()->getTimestamp() );
        $refund_date_string_default = __( 'Refund date<br />{{refund-date}}', 'woocommerce-german-market' );
        $refund_date_string = apply_filters( 'wp_wc_invoice_pdf_refund_start_refund_date',  get_option( 'wp_wc_invoice_pdf_refund_start_refund_date', $refund_date_string_default ), $refund );
        $refund_date_string = str_replace( '{{refund-date}}', $refund_date, $refund_date_string );
        ?>
        <td class="subject"><?php echo apply_filters( 'wp_wc_invoice_pdf_subject_line_1', $subject_lines[ 'wp_wc_invoice_pdf_refund_start_subject_big' ], $refund ); ?></td>
        <td class="refund-date invoice-date"><?php echo apply_filters( 'wp_wc_invoice_pdf_refund_date', $refund_date_string, $refund ); ?></td>
	</tr>
	<tr>
		<td colspan="2" class="refund-subject-line-2">
			<?php echo apply_filters( 'wp_wc_invoice_pdf_subject_line_2', $subject_lines[ 'wp_wc_invoice_pdf_refund_start_subject_small' ], $refund ); ?>
		</td>
	</tr>
</table>
<?php

//////////////////////////////////////////////////
// welcome text
//////////////////////////////////////////////////
$welcome_text	= get_option( 'wp_wc_invoice_pdf_refund_start_welcome_text', '' );
if ( trim ( $welcome_text != '' ) ) {

	if ( $can_use_order ) {
		$welcome_text_order_total = strip_tags( wc_price( abs( $refund->get_total() ), $wc_price_args ) );
	} else {
		$welcome_text_order_total = strip_tags( wc_price( rand( 1, 200 ) ) );
	}

	$welcome_text 	= apply_filters( 'wp_wc_invoice_pdf_welcome_text', str_replace( array( '{{first-name}}', '{{last-name}}', '{{order-number}}', '{{order-date}}', '{{order-total}}' ), array( $first_name, $last_name, $order_number, $order_date_formated, $welcome_text_order_total ) , $welcome_text ), $refund );
	?>
	<table class="welcome-text" cellspacing="0" cellpadding="0" border="0">
		<tr>
            <?php $welcome_text = strip_tags ( $welcome_text, '<br><br/><p><h1><h2><h3><h4><h5><h6><em><ul><li><strong><u><i><b><ol><span>' ); ?>
			<td><?php echo nl2br( $welcome_text ); ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// before order table
//////////////////////////////////////////////////
if ( $can_use_order ) {

	// remove payment instructions in refund pdf
	// but: there are plugins that use another method name instead of "email_instructions"
	$gateways           = WC_Payment_Gateways::instance();
	$available_gateways = $gateways->get_available_payment_gateways();
	$gateway            = isset( $available_gateways[ $order->get_payment_method() ] ) ? $available_gateways[ $order->get_payment_method() ] : false;
	$add_filter_again	= false;

	if ( $gateway ) {
		if ( has_action( 'woocommerce_email_before_order_table', array( $gateway, 'email_instructions' ), 10, 3 ) ) {
			remove_action( 'woocommerce_email_before_order_table', array( $gateway, 'email_instructions' ), 10, 3 );
			$add_filter_again = true;
		}
	}

	// now output actions
	ob_start();

	if ( get_option( 'wp_wc_invoice_pdf_avoid_payment_instructions', 'off' ) == 'off' ) {
		do_action( 'woocommerce_email_before_order_table', $order, false, false, $dummy_mail );
	}

	$before_order_table = ob_get_clean();

	// maybe add payment instructions again for email
	if  ( $add_filter_again ) {
		add_action( 'woocommerce_email_before_order_table', array( $gateway, 'email_instructions' ), 10, 3 );
	}

} else {
	$before_order_table = '';
}

if ( trim ( $before_order_table != '' ) ) {

	if ( apply_filters( 'wp_wc_invoice_show_before_order_table', true, $order ) ) {

		?>
		<table class="before-order-table" cellspacing="0" cellpadding="0" border="0">
			<tr>
				<td><?php echo ( trim( $before_order_table ) ); ?></td>
			</tr>
		</table>
		<?php

	}

}

/******************
* Position
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_position_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_position_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		?><td class="position"><?php echo apply_filters( 'wp_wc_invoice_pos_index', $item_i ); ?></td><?php

	}, 10, 7 );
}

/******************
* Product Image
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_image_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_image_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array(), $args = array() ) {

		$product_image_width= get_option( 'wp_wc_invoice_pdf_product_image_width', 75 );

		if ( isset( $_product ) && $_product ) {

			$html_output = WP_WC_Invoice_Pdf_Create_Pdf::use_html_output( $args );

			if ( ! wp_get_attachment_image_src( $_product->get_image_id(), 'woocommerce_thumbnail' ) ) {
				echo '<td class="product-image"></td>';
			} else {

				$file = current( wp_get_attachment_image_src( $_product->get_image_id(), 'woocommerce_thumbnail' ) );

				// build absolute server path to image (rebuild in WGM 3.0.1)
				if ( $html_output == 'no' ) { // for html debugging => outout url
					$path_array 		= wp_upload_dir();
					$path				= untrailingslashit( ( $path_array[ 'basedir' ] ) );						// wp upload path
					$url				= untrailingslashit( ( $path_array[ 'baseurl' ] ) );						// wp upload url
					$current_dir		= getcwd();
					$rel_path			= untrailingslashit( WP_WC_Invoice_Pdf_Create_Pdf::get_relative_path( $current_dir, $path ) );	// relative path from current directory to wp_upload_dir
					$rel_url			= str_replace( DIRECTORY_SEPARATOR, '/', $rel_path );
					$sub_dir_and_file	= str_replace( $url, '', $file );										// replace wp upload url from image url, will always start with a '/'

					$file_path =  $path . $sub_dir_and_file;

					// little fallback
					if ( is_file( $file_path ) ) {
						$file = $file_path;
					}

				}

				echo '<td class="product-image" style="width: ' . $product_image_width . 'px; text-align: center;">' . apply_filters( 'woocommerce_order_item_thumbnail', '<div><img src="' . $file . '" alt="' . esc_attr__( 'Product image', 'woocommerce' ) . '" width="' . $product_image_width . 'px" style="vertical-align:middle;" /></div>', $item ) . '</td>';
			}

		} else {

			echo '<td class="product-image"></td>';

		}

	}, 10, 7 );
}

/******************
* SKU
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_sku_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_sku_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		if ( $can_use_order && is_object( $_product ) && $_product->get_sku() ) {
			$sku = $_product->get_sku();
		} else {
			$sku = apply_filters( 'wp_wc_invoice_inoice_no_sku', '-', $item, $order );
		}

		?><td class="sku"><?php echo ( $can_use_order ) ? $sku : $item[ 'sku' ]; ?></td><?php

	}, 10, 7 );
}

/******************
* Product Name
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_product_name_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_product_name_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		$show_purchase_note	= get_option( 'wp_wc_invoice_pdf_show_purchase_note_in_invoice', false );
		$show_short_desc	= get_option( 'wp_wc_invoice_pdf_show_short_description_in_invoice', false );

		// product name
		$td_product_name_style = apply_filters( 'wp_wc_invvoice_pdf_td_product_name_style', '', $item );
		?><td class="product-name" style="<?php echo $td_product_name_style; ?>"><?php echo nl2br( ( $can_use_order ) ? apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) : $item[ 'name' ] );

		// item meta
		if ( $can_use_order ) {

			?><br/><?php

			if ( is_object( $_product ) ) {

				$wc_display_item_meta_args = apply_filters( 'wp_wc_invvoice_pdf_item_meta_args', array(
					'before'    => '',
		            'after'     => '',
		            'separator' => ', ',
		            'echo'      => false,
		            'autop'     => false,
				) );

				$meta_data = strip_tags( wc_display_item_meta( $item, $wc_display_item_meta_args ), '<strong><br>' );

				?><span class="smaller"><?php echo nl2br( $meta_data ); ?></span><?php

				ob_start();
				do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
				$item_meta_end = ob_get_clean();

				if ( apply_filters( 'wp_wc_invoice_pdf_show_item_meta_end', true ) ) {
					echo apply_filters( 'wp_wc_infoice_pdf_item_meta_end_markup', $item_meta_end, $item_id, $item, $order );
				}

				do_action( 'wp_wc_invvoice_pdf_item_meta_product', $item, $_product );

			}

		}

		// short description
		if ( $show_short_desc && isset( $_product ) && $_product && $can_use_order ) {

			if ( WGM_Helper::method_exists( $_product, 'get_id' ) ) {

				$short_description = WGM_Template::get_short_description_by_product_id ( $_product->get_id() );
				if ( trim( $short_description ) !== '' ) {
						?><br /><span class="short_description smaller"><?php echo $short_description; ?></span><?php
					}
			}

		}
		// purchas note
		if ( $can_use_order && $show_purchase_note && is_object( $_product ) && $purchase_note = $_product->get_purchase_note() ) {
			?><br/><span class="purchase-note"><?php echo do_shortcode( $purchase_note ); ?></span><?php
		}
		?>
		</td>
		<?php
	}, 10, 7 );
}

/******************
* Weight
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_weight_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_weight_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {
		if ( $can_use_order && is_object( $_product ) && $_product->get_weight() ) {

			$weight_unit = get_option( 'woocommerce_weight_unit', 'kg' );

			$weight = $_product->get_weight() * $item->get_quantity() . ' ' . $weight_unit;
			$weight_refunded = $_product->get_weight() * ( $item->get_quantity() + $order->get_qty_refunded_for_item( $item_id ) ) . ' ' . $weight_unit;

			if ( $weight_refunded != $weight ) {
				$weight = sprintf( '<del>%s</del><ins>%s</ins>', $weight, $weight_refunded );
			}

		} else {
			$weight = apply_filters( 'wp_wc_invoice_inoice_no_sku', '-', $item, $order );
		}
	?><td class="weight"><?php echo $weight; ?></td>
	<?php
	}, 10, 7 );
}

/******************
* Dimensions
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_dimensions_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_dimensions_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		if ( $can_use_order && is_object( $_product ) ) {

			$there_are_dimensions = false;
			foreach ( $_product->get_dimensions( false ) as $dimension ) {
				if ( ! empty( $dimension ) ) {
					$there_are_dimensions = true;
					break;
				}
			}

			if ( $there_are_dimensions ) {
				$dimensions = wc_format_dimensions( $_product->get_dimensions( false ) );
			} else {
				$dimensions = apply_filters( 'wp_wc_invoice_inoice_no_sku', '-', $item, $order );
			}

		}
		?><td class="weight"><?php echo $dimensions; ?></td>
		<?php

	}, 10, 7 );
}

/******************
* Quantity
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_quantity_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_quantity_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

	?>
	<td class="quantity"><?php

	if ( WGM_Helper::method_exists( $order, 'get_qty_refunded_for_item' ) ) {

		$qty          = $item->get_quantity();
		$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

		if ( $refunded_qty ) {
			$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
		} else {
			$qty_display = esc_html( $qty );
		}

		echo wp_kses_post( apply_filters( 'woocommerce_email_order_item_quantity', $qty_display, $item ) );

	} else {

		echo $item['qty'];

	}

	?></td><?php

	}, 10, 7 );
}

/******************
* German Market Price
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_gm_price_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_gm_price_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

	// subtotal
	if ( get_option( 'wp_wc_invoice_pdf_net_prices_product' ) == 'on' ) {

		?><td class="net_prices"><?php

			if ( $can_use_order ) {

				$tax_rate_percents = array();

				if ( is_object( $order ) &&  method_exists( $order, 'get_taxes' ) ) {
					$taxes      = $order->get_taxes();
					foreach ( $taxes as $key => $tax ) {
						$tax_rate_percents[ $tax->get_rate_id() ] = floatval( apply_filters( 'woocommerce_rate_percent', $tax->get_rate_percent(), $tax->get_rate_id() ) );
					}
				}

				// only if there is a tax
				if ( $order->get_line_tax( $item ) > 0.0 ) {

					$wc_tax 	= new WC_Tax();
					$tax_class 	= $item->get_tax_class();
					$tax_array 	= $wc_tax->get_rates( $tax_class );

					$rate_id = false;
					foreach ( $tax_array as $key => $tax ) {
						$rate_id = $key;
						break;
					}

					if ( ! $rate_id ) {

						$item_data = $item->get_data();

						if ( isset( $item_data[ 'taxes' ][ 'total' ] ) ) {

							foreach (  $item_data[ 'taxes' ][ 'total' ] as $maybe_rate_id => $tax ) {

								if ( ! empty( $tax ) ) {
									$rate_id = $maybe_rate_id;
									break;
								}
							}

						}

					}

					if ( isset( $tax_rate_percents[ $rate_id ] ) ) {
						$rate_percent = $tax_rate_percents[ $rate_id ] . '%';
					} else {
						$rate_percent = WC_Tax::get_rate_percent( $rate_id );
					}

					$rate_label = WC_Tax::get_rate_label( $rate_id );

					$net_price_product = sprintf( __( '<small>Net: %s<br />+ %s %s: %s<br /></small>= Gross: %s', 'woocommerce-german-market' ),
													wc_price( $order->get_line_subtotal( $item, false ), $wc_price_args ),
													$rate_percent,
													$rate_label,
													wc_price( $order->get_line_subtotal( $item, true ) - $order->get_line_subtotal( $item, false ), $wc_price_args ),
													wc_price( $order->get_line_subtotal( $item, true ), $wc_price_args )
										);

					$net_price_product = apply_filters( 'wp_wc_invoice_net_price_product', $net_price_product, $item );
					echo $net_price_product;

				} else {

					// there is no line tax, make default output
					echo nl2br( $can_use_order ? apply_filters( 'wp_wc_invoice_formatted_line_subtotal', $order->get_formatted_line_subtotal( $item, get_option( 'woocommerce_tax_display_cart' ) ), $item, $order ) : $item[ 'price' ] );

				}

			} else {

				$net_price_product = sprintf( __( '<small>Net: %s<br />+ %s %s: %s<br /></small>= Gross: %s', 'woocommerce-german-market' ),
												wc_price( $item[ 'price' ], $wc_price_args ),
												'19%',
												__( 'Ex. VAT', 'woocommerce-german-market' ),
												wc_price( $item[ 'price' ] * 0.19, $wc_price_args ),
												wc_price( $item[ 'price' ] * 1.19, $wc_price_args )
									);

				$net_price_product = apply_filters( 'wp_wc_invoice_net_price_product', $net_price_product, $item );
				echo $net_price_product;

			}

			?></td><?php

		} else {
			?><td class="subtotal"><?php echo nl2br( $can_use_order ? apply_filters( 'wp_wc_invoice_formatted_line_subtotal', $order->get_formatted_line_subtotal( $item, get_option( 'woocommerce_tax_display_cart' ) ), $item, $order ) : $item[ 'price' ] ); ?></td><?php
		}

	}, 10, 7 );
}

/******************
* Net Total
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_net_total_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_net_total_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		if ( ! $can_use_order ) {
			?><td class="subtotal line_tax"><?php echo __( 'Ex price', 'woocommerce-german-market' ); ?></td><?php
			return;
		}

		?><td class="subtotal total_net_price"><?php echo wc_price( $order->get_line_subtotal( $item, false ), $wc_price_args ); ?></td><?php

	}, 10, 7 );
}

/******************
* Gross Total
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_gross_total_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_gross_total_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		if ( ! $can_use_order ) {
			?><td class="subtotal line_tax"><?php echo __( 'Ex price', 'woocommerce-german-market' ); ?></td><?php
			return;
		}

		?><td class="subtotal total_gross_price"><?php echo wc_price( $order->get_line_subtotal( $item, true ), $wc_price_args ); ?></td><?php

	}, 10, 7 );
}

/******************
* Single Price Net
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_single_price_net_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_single_price_net_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		if ( ! $can_use_order ) {
			?><td class="subtotal line_tax"><?php echo __( 'Ex price', 'woocommerce-german-market' ); ?></td><?php
			return;
		}

		?><td class="subtotal single_price_net"><?php echo wc_price( $order->get_item_subtotal( $item, false ), $wc_price_args ); ?></td><?php

	}, 10, 7 );
}

/******************
* Single Price Gross
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_single_price_gross_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_single_price_gross_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		if ( ! $can_use_order ) {
			?><td class="subtotal line_tax"><?php echo __( 'Ex price', 'woocommerce-german-market' ); ?></td><?php
			return;
		}

		?><td class="subtotal single_price_gross"><?php echo wc_price( $order->get_item_subtotal( $item, true ), $wc_price_args ); ?></td><?php

	}, 10, 7 );
}

/******************
* Tax Rate
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_tax_rate_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_tax_rate_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		if ( ! $can_use_order ) {
			?><td class="subtotal line_tax">x%</td><?php
			return;
		}

		?>
		<td class="subtotal tax_rate">
			<?php
			$rate_percent = '0';

			if ( abs( $order->get_line_tax( $item ) ) > 0.0 ) {

		  		$item_data = $item->get_data();
		  		$item_tax  = array();

		  		$rate_id = false;

		  		if ( isset( $item_data[ 'taxes' ][ 'subtotal' ] ) ) {
		  			$item_tax = $item_data[ 'taxes' ][ 'subtotal' ];
		  		} else if ( isset( $item_data[ 'taxes' ][ 'total' ] ) ) {
		  			$item_tax = $item_data[ 'taxes' ][ 'total' ];
		  		}

		  		if ( ! empty( $item_tax ) ) {

		  			foreach ( $item_tax as $maybe_rate_id => $tax_amount ) {

		  				if ( empty( $tax_amount ) ) {
		  					continue;
		  				}

		  				$rate_id 	= $maybe_rate_id;
		  				break;
		  			}

		  		}

				if ( $rate_id ) {
					$rate_percent = trim( WC_Tax::get_rate_percent( $rate_id ) );
				} else {
					$rate_percent = round( $order->get_line_tax( $item ) / $order->get_line_subtotal( $item, false ) * 100, 1 ) . '%';

				}

			} else {

				// there is no line tax, make default output
				$rate_percent = '0%';

			}

			echo $rate_percent;

			?>
		</td>
		<?php

	}, 10, 7 );
}

/******************
* Line Tax Value
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_line_tax_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_line_tax_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		if ( ! $can_use_order ) {
			?><td class="subtotal line_tax"><?php echo __( 'Ex. VAT', 'woocommerce-german-market' ) ?></td><?php
			return;
		}

		$item_data = $item->get_data();
  		$line_tax  = array();

  		$line_tax = 0.0;

  		if ( isset( $item_data[ 'taxes' ][ 'subtotal' ] ) ) {
			$tax_subtotal = $item_data[ 'taxes' ][ 'subtotal' ];
			foreach ( $tax_subtotal as $rate_id => $tax_value ) {
				if ( $tax_value > 0 ) {
					$line_tax = $tax_value;
					break;
				}
			}
  		}

  		if ( $line_tax === 0.0 ) {
  			$line_tax = $order->get_line_tax( $item );
  		}

		?><td class="subtotal line_tax"><?php echo wc_price( $line_tax , $wc_price_args ); ?></td><?php

	}, 10, 7 );
}

/******************
* Item Tax Value
*****************/
if ( ! has_action( 'wp_wc_invoice_pdf_item_item_tax_refund' ) ) {
	add_action( 'wp_wc_invoice_pdf_item_item_tax_refund', function( $item, $item_id, $item_i, $_product = null, $can_use_order = true, $order = null, $wc_price_args = array() ) {

		if ( ! $can_use_order ) {
			?><td class="subtotal line_tax"><?php echo __( 'Ex. VAT', 'woocommerce-german-market' ); ?></td><?php
			return;
		}

		$item_data = $item->get_data();
  		$item_tax  = array();

  		$item_tax = 0.0;

  		if ( isset( $item_data[ 'taxes' ][ 'subtotal' ] ) ) {
			$tax_subtotal = $item_data[ 'taxes' ][ 'subtotal' ];
			foreach ( $tax_subtotal as $rate_id => $tax_value ) {
				if ( $tax_value > 0 ) {
					$item_tax = $tax_value;
					break;
				}
			}
  		}

  		if ( $item_tax === 0.0 ) {
  			$item_tax = $order->get_item_tax( $item );
  		}

  		$quantity = WGM_Helper::method_exists( $item, 'get_quantity' ) ? $item->get_quantity() : floatval( $item[ 'qty' ] );

  		if ( $quantity > 1 ) {
  			$item_tax = $item_tax / $quantity;
  		}

		?><td class="subtotal line_tax"><?php echo wc_price( $item_tax, $wc_price_args ); ?></td><?php

	}, 10, 7 );
}

$item_rows = apply_filters( 'wp_wc_invoice_pdf_item_rows_refund', array(

	'position' => array(
		'active'	=> $show_pos,
		'th'		=> '<th class="header_suk header_pos" scope="col">' . apply_filters( 'wp_wc_invoice_pos_label', __( 'Pos.', 'woocommerce-german-market' ) ) . '</th>',
		'td'		=> 'position',
	),

	'product_image'	=> array(
		'active'	=> $show_product_image,
		'th'		=> '<th class="header_suk header_image" scope="col"></th>',
		'td'		=> 'image',
	),

	'sku'	=> array(
		'active'	=> $show_sku,
		'th'		=> '<th class="header_suk header_sku" scope="col">' . __( 'SKU', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'sku',
	),

	'product_name' => array(
		'active'	=> true,
		'th'		=> '<th class="header_product" scope="col">' . __( 'Product', 'woocommerce-german-market' ) .'</th>',
		'td'		=> 'product_name',
	),

	'weight'	=> array(
		'active'	=> $show_weight,
		'th'		=> '<th class="header_suk header_weight" scope="col">' . __( 'Weight', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'weight',
	),

	'dimensions' => array(
		'active'	=> $show_dimensions,
		'th'		=> '<th class="header_suk header_dimensions" scope="col">' . __( 'Dimensions', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'dimensions',
	),

	'single_price_net' => array(
		'active'	=> false,
		'th'		=> '<th class="header_price net_single_price" scope="col">' . __( 'Single Price Net', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'single_price_net',
	),

	'single_item_tax' => array(
		'active'	=> false,
		'th'		=> '<th class="header_price item_tax_value" scope="col">' . __( 'Single Price VAT', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'item_tax',
	),

	'single_price_gross' => array(
		'active'	=> false,
		'th'		=> '<th class="header_price net_gross_price" scope="col">' . __( 'Single Price', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'single_price_gross',
	),

	'quantity'	=> array(
		'active'	=> true,
		'th'		=> '<th class="header_quantity" scope="col">' . __( 'Quantity', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'quantity',
	),

	'line_net' => array(
		'active'	=> false,
		'th'		=> '<th class="header_price net_total_price" scope="col">' . __( 'Net Price', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'net_total',
	),

	'tax_rate' => array(
		'active'	=> false,
		'th'		=> '<th class="header_price tax_percent" scope="col">' . __( 'Tax Rate', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'tax_rate',
	),

	'line_tax' => array(
		'active'	=> false,
		'th'		=> '<th class="header_price line_tax_value" scope="col">' . get_option( WGM_Helper::get_wgm_option( 'wgm_default_tax_label' ), __( 'VAT', 'woocommerce-german-market' ) ) . '</th>',
		'td'		=> 'line_tax',
	),

	'line_gross' => array(
		'active'	=> false,
		'th'		=> '<th class="header_price gross_total_price" scope="col">' . __( 'Gross Price', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'gross_total',
	),

	'gm_price' => array(
		'active'	=> true,
		'th'		=> '<th class="header_price ' . ( get_option( 'wp_wc_invoice_pdf_net_prices_product' ) == 'on' ? 'header_net_prices' : '' ) . '" scope="col">' . __( 'Price', 'woocommerce-german-market' ) . '</th>',
		'td'		=> 'gm_price',
	),

), $refund, $order );

//////////////////////////////////////////////////
// items table
//////////////////////////////////////////////////
?>
<table cellspacing="0" cellpadding="<?php echo $cell_padding;?>" class="invoice-table items-table">
    <thead>
		<tr>
			<?php
			foreach ( $item_rows as $key => $row ) {

				if ( isset( $row[ 'active' ] ) && $row[ 'active' ] ) {
					if ( isset( $row[ 'th' ] ) ) {
						echo $row[ 'th' ];
					}
				}

			}
			?>
		</tr>
    </thead>
	<tbody>
		<?php
		if ( ! $can_use_order ) {
			$items = array( 1 => array(
									'sku'		=> rand( 100, 999999 ),
									'name'		=> __( 'Ex product', 'woocommerce-german-market' ),
									'qty'		=> rand( 1, 20 ),
									'price'		=> __( 'Ex price', 'woocommerce-german-market' )
								)
						);
		}
		$item_i = 0;

		foreach ( $items as $item_id => $item ){
			$item_i++;
			if ( $test && $item_i > 2 ) {
				break;
			}

			$tr_class = '';

			if ( $can_use_order ) {

				if ( WGM_Helper::method_exists( $item, 'get_product' ) ) {
					$_product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );
				} else {
					$_product = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
				}

				$tr_class     = WGM_Helper::method_exists( $_product, 'get_type' ) ? $_product->get_type() : '';
			} else {
				$_product = null;
			}

			?>
			<tr class="<?php echo apply_filters( 'wp_wc_invoice_pdf_tr_class', $tr_class, $item );?>"><?php

				foreach ( $item_rows as $key => $row ) {

					if ( isset( $row[ 'active' ] ) && $row[ 'active' ] ) {
						if ( isset( $row[ 'td' ] ) ) {
							$hook_suffix = $row[ 'td' ];
							do_action( "wp_wc_invoice_pdf_item_{$hook_suffix}_refund", $item, $item_id, $item_i, $_product, $can_use_order, $order, $wc_price_args, $args );
						}
					}
				}

				// action after item
				if ( $can_use_order ) {
					do_action( 'wp_wc_invoice_pdf_after_item', $item, $_product );
				}
				?>
			</tr>
			<?php
		} // enf foreach ?>
	</tbody>
</table>

<?php
// we take another table because <thead> should not be repeated when page is breaking in one of the following lines
// rendering is not working correctly using <tfoot> (border-bottom of last row is missing when page breaking)
if ( $can_use_order ) {
	$totals = $order->get_order_item_totals( get_option( 'woocommerce_tax_display_cart' ) );
} else {
	$totals = array(
				array(	'label' => __( 'Cart Subtotal', 'woocommerce-german-market' ),		'value'	=> __( 'Ex price', 'woocommerce-german-market' ) ),
				array(	'label' => __( 'Shipping', 'woocommerce-german-market' ),				'value'	=> __( 'Ex price', 'woocommerce-german-market' ) ),
				array(	'label' => __( 'Order Total', 'woocommerce-german-market' ),			'value'	=> __( 'Ex price', 'woocommerce-german-market' ) ),
			);
}

?>
<table cellspacing="0" cellpadding="<?php echo $cell_padding;?>" class="invoice-table totals-table">
	<tbody>
		<?php
		if ( $totals ) {
			$i = 0;
			foreach ( $totals as $total_key => $total ) {
				$i++;
				$border_class = ( $i == 1 ) ? ' extra-border' : '';
				$colspan = ( $show_sku ) ? 3 : 2;

				if ( $show_weight ) {
					$colspan++;
				}

				if ( $show_dimensions ) {
					$colspan++;
				}

				if ( $show_product_image ) {
					$colspan++;
				}

				if ( $show_pos ) {
					$colspan++;
				}

				if ( get_option( 'wp_wc_invoice_pdf_net_prices_total' ) == 'on' ) {

					if ( $total_key == 'order_total' ) {

						?>
						<tr class="<?php echo $total_key; ?> total-net">
							<th scope="row" colspan="<?php echo $colspan; ?>" class="totals<?php echo $border_class; ?>"><?php echo apply_filters( 'wp_wc_invoice_pdf_total_net_label', __( 'Total Net:', 'woocommerce-german-market' ) ); ?></th>
							<td class="totals<?php echo $border_class; ?>">
							<?php
	                    		if ( $can_use_order ) {

		                    		$complete_taxes = $order->get_total_tax();
		                    		$total_exl_tax = $order->get_total() - $complete_taxes;
		                    		$order_net_total = $total_exl_tax;

		                    		// refund
		                    		$all_refunds = $order->get_refunds();
		                    		$refund_net_total = $order_net_total;

		                    		foreach ( $all_refunds as $the_refund ) {
		                    			$complete_taxes = $the_refund->get_total_tax();
			                    		$total_exl_tax = $the_refund->get_total() - $complete_taxes;
			                    		$refund_net_total_negative_value =  $total_exl_tax;
			                    		$refund_net_total += $refund_net_total_negative_value;
		                    		}

		                    		if ( $refund_net_total < $order_net_total ) {
		                    			echo '<del>' . wc_price( $order_net_total, $wc_price_args ) . '</del><ins>' . wc_price( $refund_net_total, $wc_price_args ) . '</ins>';
		                    		} else {
		                    			echo wc_price( $order_net_total, $wc_price_args );
		                    		}

		                    	} else {

		                    		echo __( 'Ex price', 'woocommerce-german-market' );

		                    	}
		                    ?>
	                    	</td>
						</tr>

						<?php
	                	$i++;
						$border_class = ( $i == 1 ) ? ' extra-border' : '';
						$colspan = ( $show_sku ) ? 3 : 2;

						if ( $show_weight ) {
							$colspan++;
						}

						if ( $show_dimensions ) {
							$colspan++;
						}

						if ( $show_product_image ) {
							$colspan++;
						}

						if ( $show_pos ) {
							$colspan++;
						}

					}
				}

				if ( has_action( 'wp_wc_invoice_pdf_order_total_' . $total_key ) ) {
					do_action( 'wp_wc_invoice_pdf_order_total_' . $total_key, $total, $order, $colspan, $border_class );
					continue;
				}

				?>
                <tr class="<?php echo $total_key; ?>">
                    <th scope="row" colspan="<?php echo $colspan; ?>" class="totals<?php echo $border_class; ?>"><?php echo nl2br( $total['label'] ); ?></th>
                    <td class="totals<?php echo $border_class; ?>"><?php echo nl2br( $total['value'] ); ?></td>
                </tr>
				<?php
			}
		}
		?>
		</tbody>
</table>
<?php

//////////////////////////////////////////////////
// Small Trading Exemption
//////////////////////////////////////////////////
if ( get_option( WGM_Helper::get_wgm_option( 'woocommerce_de_kleinunternehmerregelung' ) ) == 'on' ) {
	?>
	<table class="after-order-table" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td>
				<?php echo WGM_Template::get_ste_string_invoice( false, $order ); ?>
			</td>

		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// shipping address
//////////////////////////////////////////////////
if ( apply_filters( 'wp_wc_invoice_pdf_show_shipping_address', true ) ) {
	if ( ( $can_use_order && ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && $shipping_address ) || ( ! $can_use_order && $shipping_address ) ) {

		$show_shipping_address_option = get_option( 'wp_wc_invoice_pdf_show_shipping_address', 'show' );

		$show_shipping_address = true;
		if ( $show_shipping_address_option == 'show' ) {
			$show_shipping_address = true;
		} else if ( $show_shipping_address_option == 'show_only' ) {
			$show_shipping_address = $order->get_formatted_billing_address() != $order->get_formatted_shipping_address();
		} else if ( $show = 'hide' ) {
			$show_shipping_address = false;
		}

		if ( $show_shipping_address ) {

			?>
			<table class="shipping-address" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td>
						<h3 class="title"><?php echo __( 'Shipping address', 'woocommerce-german-market' ); ?>:</h3>
						<?php echo $shipping_address; ?>
					</td>
				</tr>
			</table>
			<?php

		}

	}
}

//////////////////////////////////////////////////
// after_order_table
//////////////////////////////////////////////////
if ( $can_use_order ) {
	ob_start();
	do_action( 'woocommerce_email_after_order_table', $order, false, false, $dummy_mail );
	$after_order_table = ob_get_clean();
} else {
	$after_order_table = '';
}
if ( trim( $after_order_table ) != '' ) {
	?>
	<table class="after-order-table" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td><?php echo $after_order_table; ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// order_meta
//////////////////////////////////////////////////
if ( $can_use_order ) {
	ob_start();
	do_action( 'woocommerce_email_order_meta', $order, false, false, $dummy_mail );
	$order_meta = ob_get_clean();
} else {
	$order_meta = '';
}
if ( trim( $order_meta ) != '' ) {
	?>
	<table class="order-meta" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td><?php echo $order_meta; ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// customer note
//////////////////////////////////////////////////
$show_customer_note = get_option( 'wp_wc_invoice_pdf_show_customers_note' );
if ( $show_customer_note == 'on' && $can_use_order && $order->get_customer_note() != '' ) {
	?>
	<table class="after-content-text note customer-note" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td>
				<strong><?php echo __( 'Customer Note:', 'woocommerce-german-market'); ?></strong>
			</td>
		</tr>
		<tr>
            <?php $customer_note = strip_tags ( $order->get_customer_note() ); ?>
			<td><?php echo nl2br( $customer_note); ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// order notes
//////////////////////////////////////////////////
if ( $can_use_order ) {
	$show_order_notes = get_option( 'wp_wc_invoice_pdf_show_order_notes' );
	$customer_notes = $order->get_customer_order_notes();
	if ( $show_order_notes == 'on' && $can_use_order && ! empty( $customer_notes ) ) {

		?>
		<table class="after-content-text note customer-note" cellspacing="0" cellpadding="0" border="0">
			<tr>
				<td>
					<strong><?php echo __( 'Order Notes:', 'woocommerce-german-market'); ?></strong>
				</td>
			</tr>

			<?php foreach ( $order->get_customer_order_notes() as $note ) { ?>

				<tr>
					<td>
						<?php
							echo nl2br( strip_tags( $note->comment_content ) );
							$comment_date =  date_i18n( get_option( 'date_format' ), strtotime( $note->comment_date ) ) . ' ' . date_i18n( get_option( 'time_format' ), strtotime( $note->comment_date ) );
							$comment_date = ' (' . $comment_date . ')';
							$comment_date = apply_filters( 'wp_wc_invoice_pdf_comment_date', $comment_date, $note->comment_date );
							echo $comment_date;
						?>
					</td>
				</tr>

			<?php } ?>

		</table>
		<?php
	}
}

//////////////////////////////////////////////////
// after content text
//////////////////////////////////////////////////
$after_content_text = get_option( 'wp_wc_invoice_pdf_refund_text_after_content' );
if ( $after_content_text != '' ) {
	?>
	<table class="after-content-text" cellspacing="0" cellpadding="0" border="0">
		<tr>
            <?php
            	$after_content_text = strip_tags ( $after_content_text, '<br><br/><p><h1><h2><h3><h4><h5><h6><em><ul><li><strong><u><i><ol><span>' );
            	if ( $can_use_order ) {
					$welcome_text_order_total = strip_tags( wc_price( $order->get_total(), $wc_price_args ) );
				} else {
					$welcome_text_order_total = strip_tags( wc_price( rand( 1, 200 ) ) );
				}

				$after_content_text 	= str_replace( array( '{{first-name}}', '{{last-name}}', '{{order-number}}', '{{order-date}}', '{{order-total}}' ), array( $first_name, $last_name, $order_number, $order_date_formated, $welcome_text_order_total ) , $after_content_text );

            ?>
			<td><?php echo nl2br( apply_filters( 'wp_wc_invoice_pdf_refund_text_after_content', $after_content_text, $order ) ); ?></td>
		</tr>
	</table>
	<?php
}

//////////////////////////////////////////////////
// fine print
//////////////////////////////////////////////////
$fine_print = '';
$show_fine_print = get_option( 'wp_wc_invoice_pdf_show_fine_print', 'no' );
if ( $show_fine_print != 'no' ) {
	if ( $show_fine_print == 'default' ) {
		ob_start();
		echo @wpautop( wp_kses_post( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) ); // don't show notices of third party plugins
		$fine_print_content = ( ob_get_clean() );
	} else {
		$fine_print_content = get_option( 'wp_wc_invoice_pdf_fine_print_custom_content', '' );
	}
	$fine_print_content = str_replace ( array( '<<', '>>', ), array( '<', '>', ), $fine_print_content );		// handle bugs of third party plguins
	$fine_print_content = str_replace( '<p></p>', '', $fine_print_content );									// handle bugs of third party plguins
	$fine_print_content = strip_tags ( $fine_print_content, '<br><br/><p><h1><h2><h3><h4><h5><h6><em><ul><li><strong><u><i><ol><span>' );
	if ( ! empty( trim( $fine_print_content ) ) ) {
		if ( get_option( 'wp_wc_invoice_pdf_fine_print_new_page', true ) ) {
			?><div style="page-break-after: always;"></div><?php
		}
		?><div class="fine_print"><?php echo $fine_print_content; ?></div><?php
	}
}

do_action( 'wp_wc_invoice_pdf_end_template', $args );
