<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

$update_cart_btn_classes = '';

if ( function_exists( 'wc_wp_theme_get_element_class_name' ) ) {
	$update_cart_btn_classes .= ' ' . wc_wp_theme_get_element_class_name( 'button' );
}

if ( woodmart_get_opt( 'update_cart_quantity_change' ) ) {
	$update_cart_btn_classes .= ' wd-hide';
}
?>

<div class="cart-content-wrapper">

	<?php do_action( 'woocommerce_before_cart' ); ?>

	<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

		<?php do_action( 'woocommerce_before_cart_table' ); ?>

			<table class="shop_table shop_table_responsive shop-table-with-img cart woocommerce-cart-form__contents" cellspacing="0">
				<thead>
					<tr>
						<th class="product-remove"><span class="screen-reader-text"><?php esc_html_e( 'Remove item', 'woocommerce' ); ?></span></th>
						<th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e( 'Thumbnail image', 'woocommerce' ); ?></span></th>
						<th scope="col" class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
						<th scope="col" class="product-price"><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
						<th scope="col" class="product-quantity"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
						<th scope="col" class="product-subtotal"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			$ad_materials_calc_rendered_cards = 0;

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

					if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
						$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
						$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
						$group_id       = function_exists( 'ad_materials_calc_get_group_id_from_cart_item' ) ? ad_materials_calc_get_group_id_from_cart_item( $cart_item ) : '';
						$is_grouped     = ( $group_id !== '' );
						$is_group_first = $is_grouped && function_exists( 'ad_materials_calc_is_first_group_item' ) && ad_materials_calc_is_first_group_item( $cart_item_key, $group_id );
						$is_group_last  = false;
						$is_card_start  = ! $is_grouped || $is_group_first;

						if ( $is_grouped && function_exists( 'ad_materials_calc_get_group_cart_item_keys' ) ) {
							$group_item_keys = ad_materials_calc_get_group_cart_item_keys( $group_id );
							$is_group_last   = ! empty( $group_item_keys ) && end( $group_item_keys ) === $cart_item_key;
						}

						if ( $is_card_start && $ad_materials_calc_rendered_cards > 0 ) {
						?>
					<tr class="ad-materials-calc-card-gap" aria-hidden="true">
						<td colspan="6"></td>
					</tr>
						<?php
						}

						if ( $is_group_first ) {
							$group_title    = function_exists( 'ad_materials_calc_get_group_title_from_cart_item' ) ? ad_materials_calc_get_group_title_from_cart_item( $cart_item ) : '';
							$group_label    = function_exists( 'ad_materials_calc_get_group_label_from_cart_item' ) ? ad_materials_calc_get_group_label_from_cart_item( $cart_item ) : '';
							$group_discount = function_exists( 'ad_materials_calc_get_group_discount_html_from_cart_item' ) ? ad_materials_calc_get_group_discount_html_from_cart_item( $cart_item ) : '';

							if ( $group_title === '' ) {
								$group_title = esc_html__( 'Calculated materials group', 'ad-materials-calculator' );
							}
							?>
					<tr class="ad-materials-calc-group-card-header">
						<td class="product-remove">
							<?php
						if ( function_exists( 'ad_materials_calc_build_group_remove_link_html' ) ) {
							echo ad_materials_calc_build_group_remove_link_html( $group_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
						</td>
						<td colspan="5" class="ad-materials-calc-group-card-header__content">
							<div class="ad-materials-calc-group-header">
								<div class="ad-materials-calc-group-header__title"><?php echo esc_html( $group_title ); ?></div>
								<?php if ( ( $group_label !== '' && $group_label !== $group_title ) || $group_discount !== '' ) {?>
								<div class="ad-materials-calc-group-header__meta">
									<?php if ( $group_label !== '' && $group_label !== $group_title ) {?>
									<?php echo wp_kses( $group_label, ['br' => []] ); ?>
									<?php }?>
									<?php if ( $group_discount !== '' ) {?>
									<?php if ( $group_label !== '' && $group_label !== $group_title ) {?>
									<br>
									<?php }?>
									<span class="ad-materials-calc-group-header__discount"><?php echo $group_discount; ?></span>
									<?php }?>
								</div>
								<?php }?>
							</div>
						</td>
					</tr>
					<?php
					}

					$row_classes = apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key );

					if ( $is_grouped ) {
						$row_classes .= $is_group_first ? ' ad-materials-calc-group-product-row ad-materials-calc-group-product-row-first' : ' ad-materials-calc-group-product-row ad-materials-calc-group-product-row-child';

						if ( $is_group_last ) {
							$row_classes .= ' ad-materials-calc-group-product-row-last';
						}
					} else {
						$row_classes .= ' ad-materials-calc-standalone-product-row';
					}

					if ( $is_card_start ) {
						$ad_materials_calc_rendered_cards++;
					}
						?>

						<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( trim( $row_classes ) ); ?>">

							<td class="product-remove">
								<?php
								if ( $is_grouped ) {
									echo '';
								} else {
									echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										'woocommerce_cart_item_remove_link',
										sprintf(
											'<a role="button" href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
											esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
											/* translators: %s is the product name */
											esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
											esc_attr( $product_id ),
											esc_attr( $_product->get_sku() )
										),
										$cart_item_key
									);
								}
								?>
							</td>

							<td class="product-thumbnail">
							<?php
							if ( ! $product_permalink ) {
								echo apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
							} else {
								printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key ) );
							}
							?>
							</td>

							<td class="product-name" role="rowheader" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
								<?php
									$product_name_html = '';

									if ( ! $product_permalink ) {
										$product_name_html = wp_kses_post( $product_name . '&nbsp;' );
									} else {
										$product_name_html = wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) );
									}

								$product_name_inner_class = $is_grouped ? 'ad-materials-calc-product-cell-inner ad-materials-calc-product-cell-inner--grouped' : 'ad-materials-calc-product-cell-inner';

								echo '<div class="' . esc_attr( $product_name_inner_class ) . '">';
								echo $product_name_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

								do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

								// Meta data
								echo wc_get_formatted_cart_item_data( $cart_item );

								// Backorder notification
							if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
								echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
							}

							echo '</div>';
							?>
							</td>

							<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
								<?php
									echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
								?>
							</td>

							<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
								<?php
								if ( $_product->is_sold_individually() ) {
									$min_quantity = 1;
									$max_quantity = 1;
								} else {
									$min_quantity = 0;
									$max_quantity = $_product->get_max_purchase_quantity();
								}

									$product_quantity = woocommerce_quantity_input(
										array(
											'input_name'   => "cart[{$cart_item_key}][qty]",
											'input_value'  => $cart_item['quantity'],
											'max_value'    => $max_quantity,
											'min_value'    => $min_quantity,
											'product_name' => $product_name,
										),
										$_product,
										false
									);

								echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
								?>
							</td>

							<td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
								<?php
								echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
								?>
							</td>
						</tr>

						<?php
				}
			}
			?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>

			<tr class="wd-cart-action-row">
				<td colspan="12" class="actions">
					<div class="cart-actions">
						<?php if ( wc_coupons_enabled() ) { ?>
							<div class="coupon wd-coupon-form">
								<label for="coupon_code" class="screen-reader-text">
									<?php esc_html_e( 'Coupon:', 'woocommerce' ); ?>
								</label>
								<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" />
								<button type="submit" class="button btn btn-accent<?php echo esc_attr( function_exists( 'wc_wp_theme_get_element_class_name' ) && wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>">
									<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>
								</button>
								<?php do_action( 'woocommerce_cart_coupon' ); ?>
							</div>
						<?php } ?>

						<button type="submit" class="button btn btn-default<?php echo esc_attr( $update_cart_btn_classes ); ?>" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>">
							<?php esc_html_e( 'Update cart', 'woocommerce' ); ?>
						</button>

						<?php do_action( 'woocommerce_cart_actions' ); ?>

						<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
					</div>
				</td>
			</tr>

			<?php do_action( 'woocommerce_after_cart_contents' ); ?>
			</tbody>
		</table>

		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>

	<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

	<?php
		/**
		 * Cart collaterals hook.
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action( 'woocommerce_cart_collaterals' );
	?>

	<?php do_action( 'woocommerce_after_cart' ); ?>
</div>
