<?php
/**
 * Materials Calculator - Add to cart mechanism
 *
 * Notes:
 * - Renders an "Add all to cart" form based on the current calculator results
 * - Adds all calculated items to the WooCommerce cart as one locked calculator group
 * - Prevents grouped calculator items from merging with other calculator runs
 * - Prevents grouped calculator items from being edited or removed individually
 * - Allows grouped calculator items to be removed only as a whole group
 * - Leaves standalone WooCommerce products fully editable and removable as normal
 */

/**
 * Render the add-to-cart block below the calculator results
 */
function ad_materials_calc_render_add_to_cart_block( array $answers, array $calc = [] ) {

    if ( empty( $calc ) ) {
        $calc = ad_materials_calc_get_calculation_from_answers( $answers );
    }

    $payload = ad_materials_calc_build_cart_payload_from_calc( $calc, $answers );

    if ( empty( $payload ) || empty( $payload['items'] ) ) {
        return '';
    }

    $encoded_payload = ad_materials_calc_encode_cart_payload( $payload );

    if ( $encoded_payload === '' ) {
        return '';
    }

    $payload_signature = ad_materials_calc_sign_cart_payload( $encoded_payload );

    if ( $payload_signature === '' ) {
        return '';
    }

    $html = '<div class="materials-calc-add-to-cart" style="margin-top:20px;">';
    $html .= '<form method="post">';
    $html .= wp_nonce_field( 'ad_materials_calc_add_group_to_cart', 'ad_materials_calc_add_group_nonce', true, false );
    $html .= '<input type="hidden" name="ad_materials_calc_action" value="add_group_to_cart">';
    $html .= '<input type="hidden" name="ad_materials_calc_payload" value="' . esc_attr( $encoded_payload ) . '">';
    $html .= '<input type="hidden" name="ad_materials_calc_payload_signature" value="' . esc_attr( $payload_signature ) . '">';
    $html .= '<button type="submit" class="single_add_to_cart_button button alt">';
    $html .= esc_html__( 'Add all to cart', 'ad-materials-calculator' );
    $html .= '</button>';
    $html .= '</form>';
    $html .= '</div>';

    return $html;
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

function ad_materials_calc_sign_cart_payload( $encoded_payload ) {

    $encoded_payload = is_string( $encoded_payload ) ? trim( $encoded_payload ) : '';

    if ( $encoded_payload === '' ) {
        return '';
    }

    return hash_hmac( 'sha256', $encoded_payload, wp_salt( 'ad_materials_calc_payload' ) );
}

function ad_materials_calc_is_valid_cart_payload_signature( $encoded_payload, $signature ) {

    $signature = is_string( $signature ) ? trim( $signature ) : '';
    $expected  = ad_materials_calc_sign_cart_payload( $encoded_payload );

    if ( $signature === '' || $expected === '' ) {
        return false;
    }

    return hash_equals( $expected, $signature );
}

/**
 * Build the label shown for a grouped calculator cart addition
 */
function ad_materials_calc_build_group_label( array $payload ) {

    if ( ! empty( $payload['project_meta'] ) ) {
        return (string) $payload['project_meta'];
    }

    return __( 'Calculated materials group', 'ad-materials-calculator' );
}

/**
 * Get the calculator group ID from a cart item
 */
function ad_materials_calc_get_group_id_from_cart_item( array $cart_item ) {

    return ! empty( $cart_item['ad_materials_calc_group_id'] ) ? (string) $cart_item['ad_materials_calc_group_id'] : '';
}

/**
 * Get the calculator group title from a cart item
 */
function ad_materials_calc_get_group_title_from_cart_item( array $cart_item ) {

    return ! empty( $cart_item['ad_materials_calc_group_title'] ) ? (string) $cart_item['ad_materials_calc_group_title'] : '';
}

/**
 * Get the calculator group subtitle/meta from a cart item
 */
function ad_materials_calc_get_group_label_from_cart_item( array $cart_item ) {

    return ! empty( $cart_item['ad_materials_calc_group_label'] ) ? (string) $cart_item['ad_materials_calc_group_label'] : '';
}

function ad_materials_calc_get_group_discount_type_from_cart_item( array $cart_item ) {

    return ! empty( $cart_item['ad_materials_calc_discount_type'] ) ? (string) $cart_item['ad_materials_calc_discount_type'] : 'none';
}

function ad_materials_calc_get_group_discount_label_from_cart_item( array $cart_item ) {

    return ! empty( $cart_item['ad_materials_calc_discount_label'] ) ? (string) $cart_item['ad_materials_calc_discount_label'] : '';
}

function ad_materials_calc_get_group_discount_requested_value_from_cart_item( array $cart_item ) {

    return isset( $cart_item['ad_materials_calc_discount_requested_value'] ) ? (float) $cart_item['ad_materials_calc_discount_requested_value'] : 0.0;
}

function ad_materials_calc_get_group_discount_applied_value_from_cart_item( array $cart_item ) {

    return isset( $cart_item['ad_materials_calc_discount_applied_value'] ) ? (float) $cart_item['ad_materials_calc_discount_applied_value'] : 0.0;
}

function ad_materials_calc_get_group_discount_html_from_cart_item( array $cart_item ) {

    $discount_type   = ad_materials_calc_get_group_discount_type_from_cart_item( $cart_item );
    $discount_label  = ad_materials_calc_get_group_discount_label_from_cart_item( $cart_item );
    $requested_value = ad_materials_calc_get_group_discount_requested_value_from_cart_item( $cart_item );
    $applied_value   = ad_materials_calc_get_group_discount_applied_value_from_cart_item( $cart_item );

    if ( $discount_type === 'percentage' && $requested_value > 0 ) {
        return esc_html(
            sprintf(
                '%s: %s%%',
                $discount_label !== '' ? $discount_label : __( 'Discount', 'ad-materials-calculator' ),
                rtrim( rtrim( number_format( $requested_value, 2, '.', '' ), '0' ), '.' )
            )
        );
    }

    if ( $discount_type === 'fixed' && $applied_value > 0 ) {
        return esc_html(
            sprintf(
                '%s: %s',
                $discount_label !== '' ? $discount_label : __( 'Discount', 'ad-materials-calculator' ),
                wp_strip_all_tags( wc_price( $applied_value ) )
            )
        );
    }

    return '';
}

/**
 * Check whether a cart item belongs to a calculator group
 */
function ad_materials_calc_is_grouped_cart_item( array $cart_item ) {

    return ad_materials_calc_get_group_id_from_cart_item( $cart_item ) !== '';
}

/**
 * Get all cart item keys that belong to a calculator group
 */
function ad_materials_calc_get_group_cart_item_keys( $group_id ) {

    static $group_item_keys_cache = [];

    if ( $group_id === '' || ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return [];
    }

    if ( array_key_exists( $group_id, $group_item_keys_cache ) ) {
        return $group_item_keys_cache[$group_id];
    }

    $keys = [];

    foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
        if ( ad_materials_calc_get_group_id_from_cart_item( $cart_item ) === $group_id ) {
            $keys[] = $key;
        }
    }

    $group_item_keys_cache[$group_id] = $keys;

    return $group_item_keys_cache[$group_id];
}

/**
 * Check whether the current cart item is the first item of its calculator group
 */
function ad_materials_calc_is_first_group_item( $cart_item_key, $group_id ) {

    $group_item_keys = ad_materials_calc_get_group_cart_item_keys( $group_id );

    return ! empty( $group_item_keys ) && reset( $group_item_keys ) === $cart_item_key;
}

/**
 * Build the calculator group remove URL
 */
function ad_materials_calc_build_group_remove_url( $group_id ) {

    return wp_nonce_url(
        add_query_arg(
            [
                'ad_materials_calc_action' => 'remove_group',
                'group_id'                 => $group_id,
            ],
            wc_get_cart_url()
        ),
        'ad_materials_calc_remove_group_' . $group_id
    );
}

/**
 * Build the calculator group remove link HTML for the cart remove column
 */
function ad_materials_calc_build_group_remove_link_html( $group_id ) {

    $remove_url  = ad_materials_calc_build_group_remove_url( $group_id );
    $remove_text = esc_attr__( 'Remove group from the cart', 'ad-materials-calculator' );

    return sprintf(
        '<a href="%1$s" class="remove ad-materials-calc-group-remove" aria-label="%2$s" title="%2$s">&times;</a>',
        esc_url( $remove_url ),
        $remove_text
    );
}

/**
 * Set whether a grouped calculator cart mutation is in progress for this request
 */
function ad_materials_calc_set_bulk_cart_mutation_active( $is_active ) {

    $GLOBALS['ad_materials_calc_bulk_cart_mutation_active'] = (bool) $is_active;

    return $GLOBALS['ad_materials_calc_bulk_cart_mutation_active'];
}

/**
 * Check whether a grouped calculator cart mutation is in progress for this request
 */
function ad_materials_calc_is_bulk_cart_mutation_active() {

    return ! empty( $GLOBALS['ad_materials_calc_bulk_cart_mutation_active'] );
}

/**
 * Temporarily suspend Shippo cart/shipping listeners during bulk calculator cart mutations
 */
function ad_materials_calc_suspend_shippo_rate_triggers() {

    if ( ! class_exists( '\AgencyDigital\ShippoRates\Plugin' ) ) {
        return [];
    }

    $plugin = \AgencyDigital\ShippoRates\Plugin::instance();

    if ( ! method_exists( $plugin, 'getRatesProvider' ) ) {
        return [];
    }

    $rates_provider = $plugin->getRatesProvider();

    if ( ! is_object( $rates_provider ) ) {
        return [];
    }

    $callbacks = [
        [
            'hook'     => 'woocommerce_package_rates',
            'method'   => 'addRatesToPackage',
            'priority' => 10,
            'type'     => 'filter',
        ],
        [
            'hook'     => 'woocommerce_cart_ready_to_calc_shipping',
            'method'   => 'cartReadyToCalcShipping',
            'priority' => 999,
            'type'     => 'filter',
        ],
        [
            'hook'     => 'woocommerce_checkout_init',
            'method'   => 'ensureCheckoutShipping',
            'priority' => 10,
            'type'     => 'action',
        ],
        [
            'hook'     => 'wp',
            'method'   => 'maybeCalculateShipping',
            'priority' => 5,
            'type'     => 'action',
        ],
        [
            'hook'     => 'woocommerce_before_cart',
            'method'   => 'ensureCartShipping',
            'priority' => 5,
            'type'     => 'action',
        ],
        [
            'hook'     => 'woocommerce_checkout_update_order_review',
            'method'   => 'forceShippingOnAddressUpdate',
            'priority' => 1,
            'type'     => 'action',
        ],
        [
            'hook'     => 'woocommerce_checkout_process',
            'method'   => 'ensureShippingBeforeCheckout',
            'priority' => 1,
            'type'     => 'action',
        ],
        [
            'hook'     => 'woocommerce_before_calculate_totals',
            'method'   => 'forceShippingCalculationBeforeTotals',
            'priority' => 1,
            'type'     => 'action',
        ],
        [
            'hook'     => 'woocommerce_cart_loaded_from_session',
            'method'   => 'resetCalculationFlag',
            'priority' => 1,
            'type'     => 'action',
        ],
        [
            'hook'     => 'woocommerce_update_shipping_method',
            'method'   => 'ensureShippingInFragments',
            'priority' => 10,
            'type'     => 'filter',
        ],
        [
            'hook'     => 'woocommerce_cart_item_set_quantity',
            'method'   => 'immediateShippingRecalc',
            'priority' => 999,
            'type'     => 'action',
        ],
        [
            'hook'     => 'woocommerce_after_cart_item_quantity_update',
            'method'   => 'immediateShippingRecalc',
            'priority' => 999,
            'type'     => 'action',
        ],
        [
            'hook'     => 'woocommerce_cart_item_removed',
            'method'   => 'immediateShippingRecalc',
            'priority' => 999,
            'type'     => 'action',
        ],
        [
            'hook'     => 'woocommerce_cart_item_restored',
            'method'   => 'immediateShippingRecalc',
            'priority' => 999,
            'type'     => 'action',
        ],
    ];

    $removed_callbacks = [];

    foreach ( $callbacks as $callback ) {
        $callable = [
            $rates_provider,
            $callback['method'],
        ];

        $removed = ( $callback['type'] === 'filter' )
        ? remove_filter( $callback['hook'], $callable, $callback['priority'] )
        : remove_action( $callback['hook'], $callable, $callback['priority'] );

        if ( $removed ) {
            $removed_callbacks[] = $callback;
        }
    }

    return [
        'rates_provider' => $rates_provider,
        'callbacks'      => $removed_callbacks,
    ];
}

/**
 * Restore Shippo cart/shipping listeners after bulk calculator cart mutations
 */
function ad_materials_calc_restore_shippo_rate_triggers( array $state ) {

    if ( empty( $state['rates_provider'] ) || ! is_object( $state['rates_provider'] ) || empty( $state['callbacks'] ) || ! is_array( $state['callbacks'] ) ) {
        return;
    }

    foreach ( $state['callbacks'] as $callback ) {
        $callable = [
            $state['rates_provider'],
            $callback['method'],
        ];

        if ( $callback['type'] === 'filter' ) {
            add_filter( $callback['hook'], $callable, $callback['priority'], 999 );
        } else {
            add_action( $callback['hook'], $callable, $callback['priority'], 999 );
        }
    }

}

/**
 * Skip shipping calculation while the calculator is mutating the cart in bulk
 */
add_filter( 'woocommerce_cart_ready_to_calc_shipping', 'ad_materials_calc_skip_shipping_during_bulk_cart_mutation', 999 );
function ad_materials_calc_skip_shipping_during_bulk_cart_mutation( $ready ) {

    if ( ! ad_materials_calc_is_bulk_cart_mutation_active() ) {
        return $ready;
    }

    return false;
}

/**
 * Prevent WooCommerce from building shipping packages during bulk cart mutation
 */
add_filter( 'woocommerce_shipping_packages', 'ad_materials_calc_skip_shipping_packages_during_bulk_cart_mutation', 999 );
function ad_materials_calc_skip_shipping_packages_during_bulk_cart_mutation( $packages ) {

    if ( ! ad_materials_calc_is_bulk_cart_mutation_active() ) {
        return $packages;
    }

    return [];
}

/**
 * Handle the request that adds one calculated group to the cart
 */
add_action( 'wp', 'ad_materials_calc_handle_add_group_to_cart' );
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

    $encoded_payload   = isset( $_POST['ad_materials_calc_payload'] ) ? wp_unslash( $_POST['ad_materials_calc_payload'] ) : '';
    $payload_signature = isset( $_POST['ad_materials_calc_payload_signature'] ) ? wp_unslash( $_POST['ad_materials_calc_payload_signature'] ) : '';

    if ( ! ad_materials_calc_is_valid_cart_payload_signature( $encoded_payload, $payload_signature ) ) {
        wc_add_notice( __( 'We could not validate the calculated materials. Please calculate again and try once more.', 'ad-materials-calculator' ), 'error' );
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }

    $payload = ad_materials_calc_decode_cart_payload( $encoded_payload );

    if ( empty( $payload ) || empty( $payload['items'] ) || ! is_array( $payload['items'] ) ) {
        wc_add_notice( __( 'We could not add the calculated materials to the cart. Please calculate again and try once more.', 'ad-materials-calculator' ), 'error' );
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }

    $group_id     = 'mc_' . wp_generate_uuid4();
    $group_label  = ad_materials_calc_build_group_label( $payload );
    $added_any    = false;
    $shippo_state = ad_materials_calc_suspend_shippo_rate_triggers();
    ad_materials_calc_set_bulk_cart_mutation_active( true );

    try {
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
                'ad_materials_calc_group_id'                 => $group_id,
                'ad_materials_calc_group_label'              => $group_label,
                'ad_materials_calc_group_title'              => ! empty( $payload['project_title'] ) ? (string) $payload['project_title'] : $group_label,
                'ad_materials_calc_grouped'                  => 1,
                'ad_materials_calc_discount_type'            => isset( $payload['discount_type'] ) ? (string) $payload['discount_type'] : 'none',
                'ad_materials_calc_discount_label'           => isset( $payload['discount_label'] ) ? (string) $payload['discount_label'] : '',
                'ad_materials_calc_discount_requested_value' => isset( $payload['discount_requested_value'] ) ? (float) $payload['discount_requested_value'] : 0.0,
                'ad_materials_calc_discount_applied_value'   => isset( $payload['discount_applied_value'] ) ? (float) $payload['discount_applied_value'] : 0.0,
                'ad_materials_calc_subtotal_before_discount' => isset( $payload['subtotal_before_discount'] ) ? (float) $payload['subtotal_before_discount'] : 0.0,
                'ad_materials_calc_final_total'              => isset( $payload['final_total'] ) ? (float) $payload['final_total'] : 0.0,
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
    } finally {
        ad_materials_calc_set_bulk_cart_mutation_active( false );
        ad_materials_calc_restore_shippo_rate_triggers( $shippo_state );
    }

    if ( $added_any ) {
        $calculator_url = home_url( '/materials-calculator/' );
        $success_notice = sprintf(
            wp_kses(
                __( 'The calculated materials were added to your cart as a grouped calculation. <a href="%s">Add another one?</a>', 'ad-materials-calculator' ),
                [
                    'a' => [
                        'href' => [],
                    ],
                ]
            ),
            esc_url( $calculator_url )
        );

        wc_add_notice( $success_notice, 'success' );
    } else {
        wc_add_notice( __( 'We could not add the calculated materials to the cart. Please calculate again and try once more.', 'ad-materials-calculator' ), 'error' );
    }

    wp_safe_redirect( wc_get_cart_url() );
    exit;
}

/**
 * Block single-item removal for grouped calculator items
 */
add_action( 'wp', 'ad_materials_calc_block_single_group_item_removal' );
function ad_materials_calc_block_single_group_item_removal() {

    if ( empty( $_GET['remove_item'] ) ) {
        return;
    }

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return;
    }

    $cart_item_key = wc_clean( wp_unslash( $_GET['remove_item'] ) );
    $cart          = WC()->cart->get_cart();

    if ( empty( $cart[$cart_item_key] ) ) {
        return;
    }

    if ( ! ad_materials_calc_is_grouped_cart_item( $cart[$cart_item_key] ) ) {
        return;
    }

    wc_add_notice( __( 'Calculated materials can only be removed as a whole group.', 'ad-materials-calculator' ), 'notice' );
    wp_safe_redirect( wc_get_cart_url() );
    exit;
}

/**
 * Handle the request that removes a whole calculated group
 */
add_action( 'wp', 'ad_materials_calc_handle_group_remove_request' );
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

    $shippo_state = ad_materials_calc_suspend_shippo_rate_triggers();
    ad_materials_calc_set_bulk_cart_mutation_active( true );

    try {
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( ad_materials_calc_get_group_id_from_cart_item( $cart_item ) === $group_id ) {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
        }
    } finally {
        ad_materials_calc_set_bulk_cart_mutation_active( false );
        ad_materials_calc_restore_shippo_rate_triggers( $shippo_state );
    }

    wc_add_notice( __( 'The calculated materials group was removed from your cart.', 'ad-materials-calculator' ), 'success' );
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
        wc_add_notice( __( 'Calculated materials quantities cannot be edited individually. Please remove the whole group and calculate again if needed.', 'ad-materials-calculator' ), 'error' );
        return false;
    }

    return $passed;
}

/**
 * Add remove-link titles and hide grouped item remove links
 */
add_filter( 'woocommerce_cart_item_remove_link', 'ad_materials_calc_hide_grouped_item_remove_link', 10, 2 );
function ad_materials_calc_hide_grouped_item_remove_link( $remove_link, $cart_item_key ) {

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return $remove_link;
    }

    $cart = WC()->cart->get_cart();

    if ( empty( $cart[$cart_item_key] ) ) {
        return $remove_link;
    }

    $cart_item = $cart[$cart_item_key];

    if ( ! ad_materials_calc_is_grouped_cart_item( $cart_item ) ) {
        return preg_replace(
            '/<a\b(?![^>]*\btitle=)/',
            '<a title="' . esc_attr__( 'Remove product from the cart', 'ad-materials-calculator' ) . '"',
            $remove_link,
            1
        );
    }

    return '';
}

add_action( 'woocommerce_cart_calculate_fees', 'ad_materials_calc_apply_group_discount_fees', 20 );
function ad_materials_calc_apply_group_discount_fees( $cart ) {

    if ( is_admin() && ! wp_doing_ajax() ) {
        return;
    }

    if ( ! $cart || ! is_object( $cart ) ) {
        return;
    }

    $seen_groups    = [];
    $discount_total = 0.0;

    foreach ( $cart->get_cart() as $cart_item ) {
        $group_id = ad_materials_calc_get_group_id_from_cart_item( $cart_item );

        if ( $group_id === '' || isset( $seen_groups[$group_id] ) ) {
            continue;
        }

        $seen_groups[$group_id] = true;
        $discount_total += max( 0, ad_materials_calc_get_group_discount_applied_value_from_cart_item( $cart_item ) );
    }

    if ( $discount_total <= 0 ) {
        return;
    }

    $cart->add_fee( __( 'Calculator discount', 'ad-materials-calculator' ), -$discount_total, false );
}

add_action( 'woocommerce_checkout_create_order', 'ad_materials_calc_add_group_discount_order_meta', 20, 2 );
function ad_materials_calc_add_group_discount_order_meta( $order, $data ) {

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return;
    }

    $groups      = [];
    $seen_groups = [];

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $group_id = ad_materials_calc_get_group_id_from_cart_item( $cart_item );

        if ( $group_id === '' || isset( $seen_groups[$group_id] ) ) {
            continue;
        }

        $seen_groups[$group_id] = true;

        $groups[] = [
            'group_id'                 => $group_id,
            'group_title'              => ad_materials_calc_get_group_title_from_cart_item( $cart_item ),
            'discount_type'            => ad_materials_calc_get_group_discount_type_from_cart_item( $cart_item ),
            'discount_label'           => ad_materials_calc_get_group_discount_label_from_cart_item( $cart_item ),
            'discount_requested_value' => ad_materials_calc_get_group_discount_requested_value_from_cart_item( $cart_item ),
            'discount_applied_value'   => ad_materials_calc_get_group_discount_applied_value_from_cart_item( $cart_item ),
            'subtotal_before_discount' => isset( $cart_item['ad_materials_calc_subtotal_before_discount'] ) ? (float) $cart_item['ad_materials_calc_subtotal_before_discount'] : 0.0,
            'final_total'              => isset( $cart_item['ad_materials_calc_final_total'] ) ? (float) $cart_item['ad_materials_calc_final_total'] : 0.0,
        ];
    }

    if ( empty( $groups ) ) {
        return;
    }

    $order->update_meta_data( '_ad_materials_calc_groups', $groups );
}