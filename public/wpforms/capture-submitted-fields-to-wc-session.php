<?php
/**
 * Materials Calculator - Collect all submitted fields and store them in WooCommerce session for later use
 *
 * Notes:
 * - Collect all submitted fields
 * - Normalize values
 * - Store in WooCommerce session for later use
 */

add_action( 'wpforms_process_complete', 'ad_materials_calc_capture_answers', 10, 4 );
function ad_materials_calc_capture_answers( $fields, $entry, $form_data, $entry_id ) {

    if ( empty( $form_data['id'] ) || absint( $form_data['id'] ) !== ad_materials_calc_get_wpforms_form_id() ) {
        return;
    }

    $entry_fields = ( isset( $entry['fields'] ) && is_array( $entry['fields'] ) ) ? $entry['fields'] : [];

    // Base values
    $total_area = (float) ad_materials_calc_get_entry_value( $entry_fields, 1 );

    // System: Drop-down field (#16)
    $system = ad_materials_calc_get_entry_value( $entry_fields, 16 );

    // Resolve finish-specific fields without relying on hidden fields being empty
    $surface_type = '';
    $pigment      = '';
    $finish_color = '';

    switch ( $system ) {
        case 'Terrazzo Granitech Medium':
            $surface_type = ad_materials_calc_get_entry_value( $entry_fields, 2 );
            $pigment      = ad_materials_calc_get_entry_value( $entry_fields, 23 );
            break;

        case 'Terrazzo Granitech Microterrazzo':
            $surface_type = ad_materials_calc_get_entry_value( $entry_fields, 2 );
            $pigment      = ad_materials_calc_get_entry_value( $entry_fields, 23 );
            $finish_color = ad_materials_calc_get_entry_value( $entry_fields, 30 );
            break;

        case 'Terrazzo Granitech Fine':
            $surface_type = ad_materials_calc_get_entry_value( $entry_fields, 2 );
            $pigment      = ad_materials_calc_get_entry_value( $entry_fields, 24 );
            break;

        case 'Terrazzo Granitech Gaia':
            $surface_type = ad_materials_calc_get_entry_value( $entry_fields, 48 );
            $pigment      = ad_materials_calc_get_entry_value( $entry_fields, 49 );
            break;
    }

    // Interior Floor sheen: Drop-down field (#27) or "locked" single-line text field (#29)
    $sheen_interior_floor = ad_materials_calc_get_entry_value( $entry_fields, 27 );

    if ( $sheen_interior_floor === '' ) {
        $sheen_interior_floor = ad_materials_calc_get_entry_value( $entry_fields, 29 );
    }

    // Wet Area Walls usage level depends on the selected sheen:
    // Satin => field #9, Matte Dual-Layer => field #41, Matte Single-Layer => locked field #39
    $sheen_wet_area       = ad_materials_calc_get_entry_value( $entry_fields, 8 );
    $usage_level_wet_area = '';

    if ( $sheen_wet_area === 'Satin' ) {
        $usage_level_wet_area = ad_materials_calc_get_entry_value( $entry_fields, 9 );
    } elseif ( $sheen_wet_area === 'Matte Dual-Layer' ) {
        $usage_level_wet_area = ad_materials_calc_get_entry_value( $entry_fields, 41 );
    } elseif ( $sheen_wet_area === 'Matte Single-Layer' ) {
        $usage_level_wet_area = ad_materials_calc_get_entry_value( $entry_fields, 39 );
    }

    // Gaia Wet Areas & Exterior Walls usage level mirrors the existing Wet Area Walls branching
    $sheen_gaia_wet_area       = ad_materials_calc_get_entry_value( $entry_fields, 52 );
    $usage_level_gaia_wet_area = '';

    if ( $sheen_gaia_wet_area === 'Satin' ) {
        $usage_level_gaia_wet_area = ad_materials_calc_get_entry_value( $entry_fields, 54 );
    } elseif ( $sheen_gaia_wet_area === 'Matte Dual-Layer' ) {
        $usage_level_gaia_wet_area = ad_materials_calc_get_entry_value( $entry_fields, 53 );
    } elseif ( $sheen_gaia_wet_area === 'Matte Single-Layer' ) {
        $usage_level_gaia_wet_area = ad_materials_calc_get_entry_value( $entry_fields, 56 );
    }

    $discount = ad_materials_calc_get_normalized_discount_from_entry_fields( $entry_fields );

    $answers = [
        'total_area_sqft'                  => $total_area,
        'surface_type'                     => $surface_type,
        'terrazzo_system'                  => $system,
        'pigment_color'                    => $pigment,
        'finish_color'                     => $finish_color,
        'discount_type'                    => $discount['discount_type'],
        'discount_label'                   => $discount['discount_label'],
        'discount_value'                   => $discount['discount_value'],
        'discount_allowed'                 => $discount['discount_allowed'],

        // Surface type option 1: Interior / Exterior Walls
        'substrate_type_wall'              => ad_materials_calc_get_entry_value( $entry_fields, 3 ),
        'sheen_wall'                       => ad_materials_calc_get_entry_value( $entry_fields, 4 ),
        'usage_level_wall'                 => ad_materials_calc_get_entry_value( $entry_fields, 5 ),

        // Surface type option 2: Wet Area Walls
        'sheen_wet_area'                   => $sheen_wet_area,
        'usage_level_wet_area'             => $usage_level_wet_area,

        // Surface type option 3: Interior Floor
        'moisture_exposure_interior_floor' => ad_materials_calc_get_entry_value( $entry_fields, 12 ),
        'usage_level_interior_floor'       => ad_materials_calc_get_entry_value( $entry_fields, 26 ),
        'sheen_interior_floor'             => $sheen_interior_floor,

        // Surface type option 4: Exterior Floor
        'usage_level_exterior_floor'       => ad_materials_calc_get_entry_value( $entry_fields, 15 ),

        // Case 6: Countertop / Vanity
        'sheen_countertop_vanity'          => ad_materials_calc_get_entry_value( $entry_fields, 45 ),
        'usage_level_countertop_vanity'    => ad_materials_calc_get_entry_value( $entry_fields, 46 ),

        // Case 7: Gaia Interior / Pre-Painted Drywall
        'sheen_gaia_interior'              => ad_materials_calc_get_entry_value( $entry_fields, 50 ),
        'usage_level_gaia_interior'        => ad_materials_calc_get_entry_value( $entry_fields, 51 ),

        // Case 8: Gaia Wet Areas & Exterior Walls
        'sheen_gaia_wet_area'              => $sheen_gaia_wet_area,
        'usage_level_gaia_wet_area'        => $usage_level_gaia_wet_area,
    ];

    // Store in WooCommerce session for the next step (results rendering / add-to-cart)
    if ( function_exists( 'WC' ) && WC() && WC()->session ) {
        // Important for guests: make sure WooCommerce creates/persists the session cookie
        if ( method_exists( WC()->session, 'set_customer_session_cookie' ) ) {
            WC()->session->set_customer_session_cookie( true );
        }

        WC()->session->set( 'materials_calc_answers', array_filter( $answers ) );
    }
}

/**
 * Fetch a WPForms entry field value safely by field ID
 */
function ad_materials_calc_get_entry_value( $entry_fields, $field_id ) {

    if ( ! is_array( $entry_fields ) ) {
        return '';
    }

    $field_id = absint( $field_id );

    if ( ! array_key_exists( $field_id, $entry_fields ) ) {
        return '';
    }

    return is_string( $entry_fields[$field_id] ) ? trim( $entry_fields[$field_id] ) : (string) $entry_fields[$field_id];
}

function ad_materials_calc_current_user_has_extended_privileges() {

    $allowed_user_ids = get_option( 'ad_materials_calculator_privileged_user_ids', [] );

    if ( ! is_array( $allowed_user_ids ) ) {
        return false;
    }

    $allowed_user_ids = array_filter( array_map( 'absint', $allowed_user_ids ) );

    $user_id = get_current_user_id();

    if ( ! $user_id ) {
        return false;
    }

    return in_array( (int) $user_id, $allowed_user_ids, true );
}

function ad_materials_calc_get_normalized_discount_from_entry_fields( $entry_fields ) {

    $result = [
        'discount_type'    => 'none',
        'discount_label'   => 'No discount',
        'discount_value'   => 0.0,
        'discount_allowed' => ad_materials_calc_current_user_has_extended_privileges(),
    ];

    if ( ! $result['discount_allowed'] ) {
        return $result;
    }

    $discount_type = strtolower( ad_materials_calc_get_entry_value( $entry_fields, 35 ) );

    if ( strpos( $discount_type, 'percentage' ) !== false ) {
        $value = (float) wc_format_decimal( ad_materials_calc_get_entry_value( $entry_fields, 37 ), 2 );
        $value = max( 0, min( 100, $value ) );

        return [
            'discount_type'    => 'percentage',
            'discount_label'   => 'Percentage discount',
            'discount_value'   => $value,
            'discount_allowed' => true,
        ];
    }

    if ( strpos( $discount_type, 'fixed' ) !== false ) {
        $value = (float) wc_format_decimal( ad_materials_calc_get_entry_value( $entry_fields, 38 ), wc_get_price_decimals() );
        $value = max( 0, $value );

        return [
            'discount_type'    => 'fixed',
            'discount_label'   => 'Fixed discount',
            'discount_value'   => $value,
            'discount_allowed' => true,
        ];
    }

    return $result;
}