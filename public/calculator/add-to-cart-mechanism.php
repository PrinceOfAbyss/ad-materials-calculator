<?php
/**
 * Materials Calculator - Add to cart mechanism
 *
 * Notes:
 * - Renders an "Add calculated materials to cart" form based on the current calculator results
 * - Adds all calculated items to the WooCommerce cart as one locked calculator group
 * - Prevents grouped calculator items from merging with other calculator runs
 * - Prevents grouped calculator items from being edited or removed individually
 * - Allows grouped calculator items to be removed only as a whole group
 * - Leaves standalone WooCommerce products fully editable and removable as normal
 */

/**
 * Render the add-to-cart block below the calculator results
 */
function ad_materials_calc_render_add_to_cart_block( array $answers ) {

    $payload = ad_materials_calc_build_cart_payload_from_answers( $answers );

    if ( empty( $payload ) || empty( $payload['items'] ) ) {
        return '';
    }

    $encoded_payload = ad_materials_calc_encode_cart_payload( $payload );

    if ( $encoded_payload === '' ) {
        return '';
    }

    $html  = '<div class="materials-calc-add-to-cart" style="margin-top:20px;">';
    $html .= '<form method="post">';
    $html .= wp_nonce_field( 'ad_materials_calc_add_group_to_cart', 'ad_materials_calc_add_group_nonce', true, false );
    $html .= '<input type="hidden" name="ad_materials_calc_action" value="add_group_to_cart">';
    $html .= '<input type="hidden" name="ad_materials_calc_payload" value="' . esc_attr( $encoded_payload ) . '">';
    $html .= '<button type="submit" class="single_add_to_cart_button button alt">';
    $html .= esc_html__( 'Add calculated materials to cart', 'woodmart-child' );
    $html .= '</button>';
    $html .= '</form>';
    $html .= '</div>';

    return $html;
}

/**
 * Build the cart payload from the current calculator answers
 */
function ad_materials_calc_build_cart_payload_from_answers( array $answers ) {

    $surface_type = isset( $answers['surface_type'] ) ? (string) $answers['surface_type'] : '';

    if ( $surface_type === '' ) {
        return [];
    }

    $calc = [];

    switch ( $surface_type ) {
        case 'Interior / Exterior Walls':
            $calc = ad_materials_calc_case_1_line_items( $answers );
            break;

        case 'Wet Area Walls':
            $calc = ad_materials_calc_case_2_line_items( $answers );
            break;

        case 'Interior Floor':
            $calc = ad_materials_calc_case_3_line_items( $answers );
            break;

        case 'Exterior Floor (incl. Pool Decks)':
            $calc = ad_materials_calc_case_4_line_items( $answers );
            break;

        case 'Pool Interior':
            $calc = ad_materials_calc_case_5_line_items( $answers );
            break;
    }

    if ( empty( $calc ) || ! empty( $calc['error'] ) || empty( $calc['items'] ) || ! is_array( $calc['items'] ) ) {
        return [];
    }

    $payload_items = [];

    foreach ( $calc['items'] as $item ) {
        $sku = isset( $item['sku'] ) ? trim( (string) $item['sku'] ) : '';
        $qty = isset( $item['qty'] ) ? (int) $item['qty'] : 0;

        if ( $sku === '' || $qty <= 0 ) {
            continue;
        }

        $payload_items[] = [
            'sku' => $sku,
            'qty' => $qty,
        ];
    }

    if ( empty( $payload_items ) ) {
        return [];
    }

    $system_label = isset( $answers['terrazzo_system'] ) ? (string) $answers['terrazzo_system'] : '';
    $area_sqft    = isset( $answers['total_area_sqft'] ) ? (float) $answers['total_area_sqft'] : 0;

    return [
        'surface_type' => $surface_type,
        'system_label' => $system_label,
        'area_sqft'    => $area_sqft,
        'items'        => $payload_items,
    ];
}

/**
 * Encode the calculator cart payload
 */
function ad_materials_calc_encode_cart_payload( array $payload ) {

    $json = wp_json_encode( $payload );

    if ( ! is_string( $json ) || $json === '' ) {
        return '';
    }

    return base64_encode( $json );
}

/**
 * Decode the calculator cart payload
 */
function ad_materials_calc_decode_cart_payload( $encoded_payload ) {

    $encoded_payload = is_string( $encoded_payload ) ? trim( $encoded_payload ) : '';

    if ( $encoded_payload === '' ) {
        return [];
    }

    $json = base64_decode( $encoded_payload, true );

    if ( ! is_string( $json ) || $json === '' ) {
        return [];
    }

    $payload = json_decode( $json, true );

    return is_array( $payload ) ? $payload : [];
}

/**
 * Build the label shown for a grouped calculator cart addition
 */
function ad_materials_calc_build_group_label( array $payload ) {

    $parts = [];

    if ( ! empty( $payload['surface_type'] ) ) {
        $parts[] = (string) $payload['surface_type'];
    }

    if ( ! empty( $payload['system_label'] ) ) {
        $parts[] = (string) $payload['system_label'];
    }

    if ( ! empty( $payload['area_sqft'] ) ) {
        $parts[] = rtrim( rtrim( number_format( (float) $payload['area_sqft'], 1, '.', '' ), '0' ), '.' ) . ' sq.ft.';
    }

    if ( empty( $parts ) ) {
        return __( 'Calculated materials group', 'woodmart-child' );
    }

    return implode( ' | ', $parts );
}

/**
 * Get the calculator group ID from a cart item
 */
function ad_materials_calc_get_group_id_from_cart_item( array $cart_item ) {

    return ! empty( $cart_item['ad_materials_calc_group_id'] ) ? (string) $cart_item['ad_materials_calc_group_id'] : '';
}

/**
 * Check whether a cart item belongs to a calculator group
 */
function ad_materials_calc_is_grouped_cart_item( array $cart_item ) {

    return ad_materials_calc_get_group_id_from_cart_item( $cart_item ) !== '';
}

/**
 * Check whether the current cart item is the first item of its calculator group
 */
function ad_materials_calc_is_first_group_item( $cart_item_key, $group_id ) {

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return false;
    }

    foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
        if ( ad_materials_calc_get_group_id_from_cart_item( $cart_item ) === $group_id ) {
            return $key === $cart_item_key;
        }
    }

    return false;
}

/**
 * Handle the request that adds one calculated group to the cart
 */
add_action( 'wp_loaded', 'ad_materials_calc_handle_add_group_to_cart', 5 );
function ad_materials_calc_handle_add_group_to_cart() {

    if ( empty( $_POST['ad_materials_calc_action'] ) || $_POST['ad_materials_calc_action'] !== 'add_group_to_cart' ) {
        return;
    }

    if ( empty( $_POST['ad_materials_calc_add_group_nonce'] ) || ! wp_verify_nonce( $_POST['ad_materials_calc_add_group_nonce'], 'ad_materials_calc_add_group_to_cart' ) ) {
        return;
    }

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return;
    }

    $encoded_payload = isset( $_POST['ad_materials_calc_payload'] ) ? wp_unslash( $_POST['ad_materials_calc_payload'] ) : '';
    $payload         = ad_materials_calc_decode_cart_payload( $encoded_payload );

    if ( empty( $payload ) || empty( $payload['items'] ) || ! is_array( $payload['items'] ) ) {
        wc_add_notice( __( 'We could not add the calculated materials to the cart. Please calculate again and try once more.', 'woodmart-child' ), 'error' );
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }

    $group_id    = 'mc_' . wp_generate_uuid4();
    $group_label = ad_materials_calc_build_group_label( $payload );
    $added_any   = false;

    foreach ( $payload['items'] as $payload_item ) {
        $sku = isset( $payload_item['sku'] ) ? trim( (string) $payload_item['sku'] ) : '';
        $qty = isset( $payload_item['qty'] ) ? (int) $payload_item['qty'] : 0;

        if ( $sku === '' || $qty <= 0 ) {
            continue;
        }

        $product = ad_materials_calc_get_product_by_sku( $sku );

        if ( ! $product ) {
            continue;
        }

        $cart_item_data = [
            'ad_materials_calc_group_id'    => $group_id,
            'ad_materials_calc_group_label' => $group_label,
            'ad_materials_calc_grouped'     => 1,
        ];

        if ( $product->is_type( 'variation' ) ) {
            $parent_id    = $product->get_parent_id();
            $variation_id = $product->get_id();
            $attributes   = $product->get_variation_attributes();

            $added = WC()->cart->add_to_cart(
                $parent_id,
                $qty,
                $variation_id,
                $attributes,
                $cart_item_data
            );
        } else {
            $added = WC()->cart->add_to_cart(
                $product->get_id(),
                $qty,
                0,
                [],
                $cart_item_data
            );
        }

        if ( $added ) {
            $added_any = true;
        }
    }

    if ( $added_any ) {
        wc_add_notice( __( 'The calculated materials were added to your cart as a grouped calculation.', 'woodmart-child' ), 'success' );
    } else {
        wc_add_notice( __( 'We could not add the calculated materials to the cart. Please calculate again and try once more.', 'woodmart-child' ), 'error' );
    }

    wp_safe_redirect( wc_get_cart_url() );
    exit;
}

/**
 * Block single-item removal for grouped calculator items
 */
add_action( 'wp_loaded', 'ad_materials_calc_block_single_group_item_removal', 1 );
function ad_materials_calc_block_single_group_item_removal() {

    if ( empty( $_GET['remove_item'] ) ) {
        return;
    }

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return;
    }

    $cart_item_key = wc_clean( wp_unslash( $_GET['remove_item'] ) );
    $cart          = WC()->cart->get_cart();

    if ( empty( $cart[ $cart_item_key ] ) ) {
        return;
    }

    if ( ! ad_materials_calc_is_grouped_cart_item( $cart[ $cart_item_key ] ) ) {
        return;
    }

    wc_add_notice( __( 'Calculated materials can only be removed as a whole group.', 'woodmart-child' ), 'notice' );
    wp_safe_redirect( wc_get_cart_url() );
    exit;
}

/**
 * Handle the request that removes a whole calculated group
 */
add_action( 'wp_loaded', 'ad_materials_calc_handle_group_remove_request', 6 );
function ad_materials_calc_handle_group_remove_request() {

    if ( empty( $_GET['ad_materials_calc_action'] ) || $_GET['ad_materials_calc_action'] !== 'remove_group' ) {
        return;
    }

    if ( empty( $_GET['group_id'] ) ) {
        return;
    }

    $group_id = wc_clean( wp_unslash( $_GET['group_id'] ) );

    if ( ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '', 'ad_materials_calc_remove_group_' . $group_id ) ) {
        return;
    }

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return;
    }

    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        if ( ad_materials_calc_get_group_id_from_cart_item( $cart_item ) === $group_id ) {
            WC()->cart->remove_cart_item( $cart_item_key );
        }
    }

    wc_add_notice( __( 'The calculated materials group was removed from your cart.', 'woodmart-child' ), 'success' );
    wp_safe_redirect( wc_get_cart_url() );
    exit;
}

/**
 * Lock grouped calculator quantities in the cart
 */
add_filter( 'woocommerce_cart_item_quantity', 'ad_materials_calc_render_locked_group_quantity', 10, 3 );
function ad_materials_calc_render_locked_group_quantity( $product_quantity, $cart_item_key, $cart_item ) {

    if ( ! ad_materials_calc_is_grouped_cart_item( $cart_item ) ) {
        return $product_quantity;
    }

    return '<span class="ad-materials-calc-locked-qty">' . esc_html( (int) $cart_item['quantity'] ) . '</span>';
}

/**
 * Prevent quantity updates for grouped calculator items
 */
add_filter( 'woocommerce_update_cart_validation', 'ad_materials_calc_block_group_quantity_updates', 10, 4 );
function ad_materials_calc_block_group_quantity_updates( $passed, $cart_item_key, $cart_item, $quantity ) {

    if ( ! ad_materials_calc_is_grouped_cart_item( $cart_item ) ) {
        return $passed;
    }

    if ( (int) $quantity !== (int) $cart_item['quantity'] ) {
        wc_add_notice( __( 'Calculated materials quantities cannot be edited individually. Please remove the whole group and calculate again if needed.', 'woodmart-child' ), 'error' );
        return false;
    }

    return $passed;
}

/**
 * Hide the default remove link for grouped calculator items
 */
add_filter( 'woocommerce_cart_item_remove_link', 'ad_materials_calc_hide_grouped_item_remove_link', 10, 2 );
function ad_materials_calc_hide_grouped_item_remove_link( $remove_link, $cart_item_key ) {

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return $remove_link;
    }

    $cart = WC()->cart->get_cart();

    if ( empty( $cart[ $cart_item_key ] ) ) {
        return $remove_link;
    }

    if ( ! ad_materials_calc_is_grouped_cart_item( $cart[ $cart_item_key ] ) ) {
        return $remove_link;
    }

    return '';
}

/**
 * Render the group label and group remove link under the first grouped item
 */
add_action( 'woocommerce_after_cart_item_name', 'ad_materials_calc_render_group_meta_under_first_item', 10, 2 );
function ad_materials_calc_render_group_meta_under_first_item( $cart_item, $cart_item_key ) {

    $group_id = ad_materials_calc_get_group_id_from_cart_item( $cart_item );

    if ( $group_id === '' ) {
        return;
    }

    if ( ! ad_materials_calc_is_first_group_item( $cart_item_key, $group_id ) ) {
        return;
    }

    $group_label = ! empty( $cart_item['ad_materials_calc_group_label'] ) ? (string) $cart_item['ad_materials_calc_group_label'] : __( 'Calculated materials group', 'woodmart-child' );

    $remove_url = wp_nonce_url(
        add_query_arg(
            [
                'ad_materials_calc_action' => 'remove_group',
                'group_id'                 => $group_id,
            ],
            wc_get_cart_url()
        ),
        'ad_materials_calc_remove_group_' . $group_id
    );

    echo '<div class="ad-materials-calc-group-meta" style="margin-top:8px;">';
    echo '<div style="font-size:12px;opacity:.8;">' . esc_html( $group_label ) . '</div>';
    echo '<a href="' . esc_url( $remove_url ) . '" style="font-size:12px;">' . esc_html__( 'Remove calculation group', 'woodmart-child' ) . '</a>';
    echo '</div>';
}