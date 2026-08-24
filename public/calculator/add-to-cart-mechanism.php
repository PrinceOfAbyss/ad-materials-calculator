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

    $html = '<div class="materials-calc-add-to-cart">';
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

function ad_materials_calc_get_unit_price_from_cart_item( array $cart_item ) {

    return isset( $cart_item['ad_materials_calc_unit_price'] ) ? ad_materials_calc_sanitize_unit_price( $cart_item['ad_materials_calc_unit_price'] ) : 0.0;
}

function ad_materials_calc_get_group_share_link_id_from_cart_item( array $cart_item ) {

    return isset( $cart_item['ad_materials_calc_share_link_id'] ) ? absint( $cart_item['ad_materials_calc_share_link_id'] ) : 0;
}

function ad_materials_calc_get_group_discount_html_from_cart_item( array $cart_item ) {

    $discount_type   = ad_materials_calc_get_group_discount_type_from_cart_item( $cart_item );
    $discount_label  = ad_materials_calc_get_group_discount_label_from_cart_item( $cart_item );
    $requested_value = ad_materials_calc_get_group_discount_requested_value_from_cart_item( $cart_item );

    if ( $discount_type === 'percentage' && $requested_value > 0 ) {
        return esc_html(
            sprintf(
                '%s: %s%%',
                $discount_label !== '' ? $discount_label : __( 'Discount', 'ad-materials-calculator' ),
                rtrim( rtrim( number_format( $requested_value, 2, '.', '' ), '0' ), '.' )
            )
        );
    }

    if ( $discount_type === 'fixed' && $requested_value > 0 ) {
        return esc_html(
            sprintf(
                '%s: %s',
                $discount_label !== '' ? $discount_label : __( 'Discount', 'ad-materials-calculator' ),
                wp_strip_all_tags( wc_price( $requested_value ) )
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

    $added_any = ad_materials_calc_add_payload_group_to_cart( $payload );

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
 * Handle a public share link and restore its calculated group into the cart.
 */
add_action( 'wp', 'ad_materials_calc_handle_share_link_restore' );
function ad_materials_calc_handle_share_link_restore() {

    if ( empty( $_GET['mc_share'] ) ) {
        return;
    }

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return;
    }

    $token  = wc_clean( wp_unslash( $_GET['mc_share'] ) );
    $record = Ad_Materials_Calculator_Share_Links::get_by_token( $token );

    if ( empty( $record ) || ! Ad_Materials_Calculator_Share_Links::is_record_valid( $record ) ) {
        ad_materials_calc_redirect_to_calculator_with_share_notice( 'expired' );
    }

    $payload = Ad_Materials_Calculator_Share_Links::get_payload_from_record( $record );

    if ( empty( $payload ) ) {
        ad_materials_calc_redirect_to_calculator_with_share_notice( 'invalid' );
    }

    ad_materials_calc_empty_cart_for_share_link_restore();

    $share_link_id = isset( $record['id'] ) ? (int) $record['id'] : 0;
    $added         = false;

    if ( ! empty( $payload['groups'] ) && is_array( $payload['groups'] ) ) {
        foreach ( $payload['groups'] as $group_payload ) {
            if ( ad_materials_calc_add_payload_group_to_cart( $group_payload, $share_link_id ) ) {
                $added = true;
            }
        }
    }

    if ( ! empty( $payload['items'] ) && is_array( $payload['items'] ) ) {
        if ( ad_materials_calc_add_payload_items_to_cart( $payload['items'], $share_link_id ) ) {
            $added = true;
        }
    }

    if ( ! $added ) {
        ad_materials_calc_redirect_to_calculator_with_share_notice( 'invalid' );
    }

    Ad_Materials_Calculator_Share_Links::mark_accessed( isset( $record['id'] ) ? (int) $record['id'] : 0 );

    wc_add_notice( __( 'The shared materials were added to your cart.', 'ad-materials-calculator' ), 'success' );
    wp_safe_redirect( wc_get_checkout_url() );
    exit;
}

function ad_materials_calc_empty_cart_for_share_link_restore() {

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return;
    }

    $shippo_state = ad_materials_calc_suspend_shippo_rate_triggers();
    ad_materials_calc_set_bulk_cart_mutation_active( true );

    try {
        WC()->cart->empty_cart();
    } finally {
        ad_materials_calc_set_bulk_cart_mutation_active( false );
        ad_materials_calc_restore_shippo_rate_triggers( $shippo_state );
    }
}

function ad_materials_calc_redirect_to_calculator_with_share_notice( $notice_type ) {

    $notice_type = sanitize_key( $notice_type );

    wp_safe_redirect(
        add_query_arg(
            'mc_share_notice',
            $notice_type,
            home_url( '/materials-calculator/' )
        )
    );
    exit;
}

add_filter( 'the_content', 'ad_materials_calc_prepend_share_link_notice_to_calculator_page' );
function ad_materials_calc_prepend_share_link_notice_to_calculator_page( $content ) {

    if ( ! is_main_query() || ! in_the_loop() || ! is_page( 'materials-calculator' ) || empty( $_GET['mc_share_notice'] ) ) {
        return $content;
    }

    $notice_type = sanitize_key( wp_unslash( $_GET['mc_share_notice'] ) );

    if ( $notice_type === 'expired' ) {
        $notice = sprintf(
            wp_kses(
                __( '<strong>THIS LINK HAS EXPIRED!</strong> The link you clicked was valid for 30 days. Please use the calculator below to generate a new list of supplies required for your project. Or click <a href="%s">here</a> to reach our support staff for further assistance.', 'ad-materials-calculator' ),
                [
                    'strong' => [],
                    'a'      => [
                        'href' => [],
                    ],
                ]
            ),
            esc_url( home_url( '/contact/' ) )
        );
    } elseif ( $notice_type === 'invalid' ) {
        $notice = esc_html__( 'This shared materials cart could not be loaded. Please create a new calculation to continue.', 'ad-materials-calculator' );
    } else {
        return $content;
    }

    return '<div class="woocommerce-notices-wrapper"><div class="woocommerce-info ad-materials-calc-share-notice">' . $notice . '</div></div>' . $content;
}

add_action( 'woocommerce_proceed_to_checkout', 'ad_materials_calc_render_share_cart_button', 30 );
function ad_materials_calc_render_share_cart_button() {

    if ( ! function_exists( 'ad_materials_calc_current_user_has_extended_privileges' ) || ! ad_materials_calc_current_user_has_extended_privileges() ) {
        return;
    }

    if ( empty( ad_materials_calc_build_share_cart_payload_from_cart() ) ) {
        return;
    }

    echo '<button type="button" class="checkout-button button alt wc-forward ad-materials-calc-share-cart-button" data-share-nonce="' . esc_attr( wp_create_nonce( 'ad_materials_calc_create_cart_share_link' ) ) . '">';
    echo esc_html__( 'Share the cart', 'ad-materials-calculator' );
    echo '</button>';
}

function ad_materials_calc_build_share_cart_payload_from_cart() {

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return [];
    }

    $groups      = [];
    $standalones = [];

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( empty( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) || ! method_exists( $cart_item['data'], 'get_sku' ) ) {
            continue;
        }

        $sku = trim( (string) $cart_item['data']->get_sku() );
        $qty = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;

        if ( $sku === '' || $qty <= 0 ) {
            continue;
        }

        $group_id = ad_materials_calc_get_group_id_from_cart_item( $cart_item );

        if ( $group_id === '' ) {
            $standalones[] = [
                'sku' => $sku,
                'qty' => $qty,
            ];

            continue;
        }

        if ( empty( $groups[$group_id] ) ) {
            $group_subtotal = ad_materials_calc_get_current_group_subtotal( WC()->cart, $group_id );
            $group_discount = ad_materials_calc_get_current_group_discount_amount( WC()->cart, $group_id, $cart_item );

            $groups[$group_id] = [
                'project_title'            => ad_materials_calc_get_group_title_from_cart_item( $cart_item ),
                'project_meta'             => ad_materials_calc_get_group_label_from_cart_item( $cart_item ),
                'discount_type'            => ad_materials_calc_get_group_discount_type_from_cart_item( $cart_item ),
                'discount_label'           => ad_materials_calc_get_group_discount_label_from_cart_item( $cart_item ),
                'discount_requested_value' => ad_materials_calc_get_group_discount_requested_value_from_cart_item( $cart_item ),
                'discount_applied_value'   => $group_discount,
                'subtotal_before_discount' => $group_subtotal,
                'final_total'              => max( 0, $group_subtotal - $group_discount ),
                'items'                    => [],
            ];
        }

        $groups[$group_id]['items'][] = [
            'sku' => $sku,
            'qty' => $qty,
        ];
    }

    $groups = array_values(
        array_filter(
            $groups,
            function ( $group ) {
                return ! empty( $group['items'] );
            }
        )
    );

    if ( empty( $groups ) && empty( $standalones ) ) {
        return [];
    }

    $payload = [
        'groups' => $groups,
    ];

    if ( ! empty( $standalones ) ) {
        $payload['items']         = $standalones;
        $payload['project_title'] = __( 'Standalone cart products', 'ad-materials-calculator' );
    }

    return $payload;
}

/**
 * Create a share link for privileged staff users from the current cart groups.
 */
add_action( 'wp_ajax_ad_materials_calc_create_cart_share_link', 'ad_materials_calc_ajax_create_cart_share_link' );
function ad_materials_calc_ajax_create_cart_share_link() {

    if ( ! function_exists( 'ad_materials_calc_current_user_has_extended_privileges' ) || ! ad_materials_calc_current_user_has_extended_privileges() ) {
        wp_send_json_error(
            [
                'message' => __( 'You do not have permission to create share links.', 'ad-materials-calculator' ),
            ],
            403
        );
    }

    if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'ad_materials_calc_create_cart_share_link' ) ) {
        wp_send_json_error(
            [
                'message' => __( 'We could not validate this share-link request. Please refresh and try again.', 'ad-materials-calculator' ),
            ],
            403
        );
    }

    $payload = ad_materials_calc_build_share_cart_payload_from_cart();

    $has_groups = ! empty( $payload['groups'] ) && is_array( $payload['groups'] );
    $has_items  = ! empty( $payload['items'] ) && is_array( $payload['items'] );

    if ( ! $has_groups && ! $has_items ) {
        wp_send_json_error(
            [
                'message' => __( 'Add at least one product to the cart before creating a share link.', 'ad-materials-calculator' ),
            ],
            400
        );
    }

    $share = Ad_Materials_Calculator_Share_Links::create( $payload );

    if ( empty( $share['url'] ) ) {
        wp_send_json_error(
            [
                'message' => __( 'We could not create a share link right now. Please try again.', 'ad-materials-calculator' ),
            ],
            500
        );
    }

    wp_send_json_success(
        [
            'url'        => $share['url'],
            'expires_at' => isset( $share['expires_at'] ) ? $share['expires_at'] : '',
        ]
    );
}

function ad_materials_calc_add_payload_group_to_cart( array $payload, $share_link_id = 0 ) {

    if ( empty( $payload['items'] ) || ! is_array( $payload['items'] ) || ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return false;
    }

    $group_id      = 'mc_' . wp_generate_uuid4();
    $group_label   = ad_materials_calc_build_group_label( $payload );
    $added_any     = false;
    $share_link_id = absint( $share_link_id );
    $shippo_state  = ad_materials_calc_suspend_shippo_rate_triggers();
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

            $unit_price = $share_link_id <= 0 && isset( $payload_item['unit_price'] ) ? ad_materials_calc_sanitize_unit_price( $payload_item['unit_price'] ) : 0.0;

            if ( $unit_price <= 0 && function_exists( 'ad_materials_calc_get_product_normal_unit_price' ) ) {
                $unit_price = ad_materials_calc_get_product_normal_unit_price( $product );
            }

            $cart_item_data = [
                'ad_materials_calc_group_id'                 => $group_id,
                'ad_materials_calc_group_label'              => $group_label,
                'ad_materials_calc_group_title'              => ! empty( $payload['project_title'] ) ? (string) $payload['project_title'] : $group_label,
                'ad_materials_calc_grouped'                  => 1,
                'ad_materials_calc_unit_price'               => $unit_price,
                'ad_materials_calc_pricing_tier'             => ! empty( $payload['pricing_tier'] ) ? sanitize_key( (string) $payload['pricing_tier'] ) : 'normal',
                'ad_materials_calc_discount_type'            => isset( $payload['discount_type'] ) ? (string) $payload['discount_type'] : 'none',
                'ad_materials_calc_discount_label'           => isset( $payload['discount_label'] ) ? (string) $payload['discount_label'] : '',
                'ad_materials_calc_discount_requested_value' => isset( $payload['discount_requested_value'] ) ? (float) $payload['discount_requested_value'] : 0.0,
                'ad_materials_calc_subtotal_before_discount' => isset( $payload['subtotal_before_discount'] ) ? (float) $payload['subtotal_before_discount'] : 0.0,
                'ad_materials_calc_final_total'              => isset( $payload['final_total'] ) ? (float) $payload['final_total'] : 0.0,
                'ad_materials_calc_share_link_id'            => $share_link_id,
            ];

            if ( $product->is_type( 'variation' ) ) {
                $added = WC()->cart->add_to_cart(
                    $product->get_parent_id(),
                    $qty,
                    $product->get_id(),
                    $product->get_variation_attributes(),
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

    return $added_any;
}

function ad_materials_calc_add_payload_items_to_cart( array $items, $share_link_id = 0 ) {

    if ( empty( $items ) || ! is_array( $items ) || ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return false;
    }

    $added_any     = false;
    $share_link_id = absint( $share_link_id );
    $shippo_state  = ad_materials_calc_suspend_shippo_rate_triggers();
    ad_materials_calc_set_bulk_cart_mutation_active( true );

    try {
        foreach ( $items as $payload_item ) {
            $sku = isset( $payload_item['sku'] ) ? trim( (string) $payload_item['sku'] ) : '';
            $qty = isset( $payload_item['qty'] ) ? (int) $payload_item['qty'] : 0;

            if ( $sku === '' || $qty <= 0 ) {
                continue;
            }

            $product = ad_materials_calc_get_product_by_sku( $sku );

            if ( ! $product ) {
                continue;
            }

            $cart_item_data = [];

            if ( $share_link_id > 0 ) {
                $cart_item_data['ad_materials_calc_share_link_id'] = $share_link_id;
            }

            if ( $product->is_type( 'variation' ) ) {
                $added = WC()->cart->add_to_cart(
                    $product->get_parent_id(),
                    $qty,
                    $product->get_id(),
                    $product->get_variation_attributes(),
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

    return $added_any;
}

/**
 * Show a cart notice when non-staff users modify calculated recommendations
 */
function ad_materials_calc_add_recommended_quantities_changed_notice() {

    if ( ad_materials_calc_current_user_has_extended_privileges() ) {
        return;
    }

    if ( ! ad_materials_calc_cart_has_grouped_items() ) {
        return;
    }

    $message = __( 'Attention! You have changed the recommended quantities.', 'ad-materials-calculator' );

    if ( function_exists( 'wc_has_notice' ) && wc_has_notice( $message, 'notice' ) ) {
        return;
    }

    wc_add_notice( $message, 'notice' );
}

/**
 * Check whether the cart still contains calculated grouped materials
 */
function ad_materials_calc_cart_has_grouped_items() {

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return false;
    }

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( ad_materials_calc_is_grouped_cart_item( $cart_item ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Add the recommendation notice when a single calculated material is removed
 */
add_action( 'wp_loaded', 'ad_materials_calc_notice_single_group_item_removal', 10 );
function ad_materials_calc_notice_single_group_item_removal() {

    if ( empty( $_GET['remove_item'] ) ) {
        return;
    }

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return;
    }

    $cart_item_key = wc_clean( wp_unslash( $_GET['remove_item'] ) );
    $cart          = WC()->cart->get_cart();

    if ( empty( $cart[$cart_item_key] ) || ! ad_materials_calc_is_grouped_cart_item( $cart[$cart_item_key] ) ) {
        return;
    }

    if ( ad_materials_calc_count_grouped_cart_items() <= 1 ) {
        return;
    }

    ad_materials_calc_add_recommended_quantities_changed_notice();
}

/**
 * Count calculated grouped materials currently in the cart
 */
function ad_materials_calc_count_grouped_cart_items() {

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return 0;
    }

    $count = 0;

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( ad_materials_calc_is_grouped_cart_item( $cart_item ) ) {
            $count++;
        }
    }

    return $count;
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

    ad_materials_calc_add_recommended_quantities_changed_notice();

    wc_add_notice( __( 'The calculated materials group was removed from your cart.', 'ad-materials-calculator' ), 'success' );
    wp_safe_redirect( wc_get_cart_url() );
    exit;
}

/**
 * Add the recommendation notice when calculated quantities are edited
 */
add_filter( 'woocommerce_update_cart_validation', 'ad_materials_calc_notice_group_quantity_updates', 10, 4 );
function ad_materials_calc_notice_group_quantity_updates( $passed, $cart_item_key, $cart_item, $quantity ) {

    if ( ad_materials_calc_is_grouped_cart_item( $cart_item ) && (int) $quantity !== (int) $cart_item['quantity'] ) {
        ad_materials_calc_add_recommended_quantities_changed_notice();
    }

    return $passed;
}

add_action( 'woocommerce_before_calculate_totals', 'ad_materials_calc_apply_group_item_unit_prices', 9999 );
function ad_materials_calc_apply_group_item_unit_prices( $cart ) {

    if ( is_admin() && ! wp_doing_ajax() ) {
        return;
    }

    if ( ! $cart || ! is_object( $cart ) ) {
        return;
    }

    foreach ( $cart->get_cart() as $cart_item ) {
        if ( ! ad_materials_calc_is_grouped_cart_item( $cart_item ) || empty( $cart_item['data'] ) || ! is_object( $cart_item['data'] ) || ! method_exists( $cart_item['data'], 'set_price' ) ) {
            continue;
        }

        $unit_price = ad_materials_calc_get_unit_price_from_cart_item( $cart_item );

        if ( $unit_price <= 0 ) {
            continue;
        }

        $cart_item['data']->set_price( $unit_price );
    }
}

/**
 * Add remove-link titles
 */
add_filter( 'woocommerce_cart_item_remove_link', 'ad_materials_calc_add_cart_item_remove_link_title', 10, 1 );
function ad_materials_calc_add_cart_item_remove_link_title( $remove_link ) {

    return preg_replace(
        '/<a\b(?![^>]*\btitle=)/',
        '<a title="' . esc_attr__( 'Remove product from the cart', 'ad-materials-calculator' ) . '"',
        $remove_link,
        1
    );
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
        $discount_total += ad_materials_calc_get_current_group_discount_amount( $cart, $group_id, $cart_item );
    }

    if ( $discount_total <= 0 ) {
        return;
    }

    $cart->add_fee( __( 'Calculator discount', 'ad-materials-calculator' ), -$discount_total, false );
}

function ad_materials_calc_get_current_group_subtotal( $cart, $group_id ) {

    $subtotal = 0.0;

    if ( ! $cart || ! is_object( $cart ) || $group_id === '' ) {
        return $subtotal;
    }

    foreach ( $cart->get_cart() as $cart_item ) {
        if ( ad_materials_calc_get_group_id_from_cart_item( $cart_item ) !== $group_id ) {
            continue;
        }

        $subtotal += isset( $cart_item['line_subtotal'] ) ? (float) $cart_item['line_subtotal'] : 0.0;
    }

    return max( 0, $subtotal );
}

function ad_materials_calc_get_current_group_discount_amount( $cart, $group_id, array $cart_item ) {

    $discount_type   = ad_materials_calc_get_group_discount_type_from_cart_item( $cart_item );
    $requested_value = ad_materials_calc_get_group_discount_requested_value_from_cart_item( $cart_item );
    $group_subtotal  = ad_materials_calc_get_current_group_subtotal( $cart, $group_id );

    if ( $group_subtotal <= 0 || $requested_value <= 0 ) {
        return 0.0;
    }

    if ( $discount_type === 'percentage' ) {
        return round( $group_subtotal * ( min( 100, max( 0, $requested_value ) ) / 100 ), wc_get_price_decimals() );
    }

    if ( $discount_type === 'fixed' ) {
        return min( max( 0, $requested_value ), $group_subtotal );
    }

    return 0.0;
}

add_action( 'woocommerce_checkout_create_order', 'ad_materials_calc_add_group_discount_order_meta', 20, 2 );
function ad_materials_calc_add_group_discount_order_meta( $order, $data ) {

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
        return;
    }

    $groups         = [];
    $seen_groups    = [];
    $share_link_ids = [];

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $share_link_id = ad_materials_calc_get_group_share_link_id_from_cart_item( $cart_item );

        if ( $share_link_id > 0 ) {
            $share_link_ids[] = $share_link_id;
        }

        $group_id = ad_materials_calc_get_group_id_from_cart_item( $cart_item );

        if ( $group_id === '' || isset( $seen_groups[$group_id] ) ) {
            continue;
        }

        $seen_groups[$group_id] = true;
        $group_subtotal         = ad_materials_calc_get_current_group_subtotal( WC()->cart, $group_id );
        $group_discount         = ad_materials_calc_get_current_group_discount_amount( WC()->cart, $group_id, $cart_item );

        $groups[] = [
            'group_id'                 => $group_id,
            'group_title'              => ad_materials_calc_get_group_title_from_cart_item( $cart_item ),
            'group_label'              => ad_materials_calc_get_group_label_from_cart_item( $cart_item ),
            'discount_type'            => ad_materials_calc_get_group_discount_type_from_cart_item( $cart_item ),
            'discount_label'           => ad_materials_calc_get_group_discount_label_from_cart_item( $cart_item ),
            'discount_requested_value' => ad_materials_calc_get_group_discount_requested_value_from_cart_item( $cart_item ),
            'discount_applied_value'   => $group_discount,
            'subtotal_before_discount' => $group_subtotal,
            'final_total'              => max( 0, $group_subtotal - $group_discount ),
            'pricing_tier'             => ! empty( $cart_item['ad_materials_calc_pricing_tier'] ) ? sanitize_key( (string) $cart_item['ad_materials_calc_pricing_tier'] ) : 'normal',
            'share_link_id'            => ad_materials_calc_get_group_share_link_id_from_cart_item( $cart_item ),
        ];
    }

    if ( ! empty( $groups ) ) {
        $order->update_meta_data( '_ad_materials_calc_groups', $groups );
    }

    $share_link_ids = array_values(
        array_unique(
            array_filter(
                array_map( 'absint', $share_link_ids )
            )
        )
    );

    if ( ! empty( $share_link_ids ) ) {
        $order->update_meta_data( '_ad_materials_calc_share_link_ids', $share_link_ids );
    }
}

add_action( 'woocommerce_payment_complete', 'ad_materials_calc_mark_share_links_used_by_order' );
add_action( 'woocommerce_order_status_processing', 'ad_materials_calc_mark_share_links_used_by_order' );
add_action( 'woocommerce_order_status_completed', 'ad_materials_calc_mark_share_links_used_by_order' );
function ad_materials_calc_mark_share_links_used_by_order( $order_id ) {

    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return;
    }

    $share_link_ids = $order->get_meta( '_ad_materials_calc_share_link_ids', true );

    if ( empty( $share_link_ids ) || ! is_array( $share_link_ids ) ) {
        return;
    }

    foreach ( array_filter( array_map( 'absint', $share_link_ids ) ) as $share_link_id ) {
        Ad_Materials_Calculator_Share_Links::mark_used_by_order( $share_link_id, $order->get_id() );
    }
}