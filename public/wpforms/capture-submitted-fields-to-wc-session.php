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

    if ( empty( $form_data['id'] ) || absint( $form_data['id'] ) !== 11307 ) {
        return;
    }

    $entry_fields = ( isset( $entry['fields'] ) && is_array( $entry['fields'] ) ) ? $entry['fields'] : [];

    // Base values
    $total_area   = (float) ad_materials_calc_get_entry_value( $entry_fields, 1 );
    $surface_type = ad_materials_calc_get_entry_value( $entry_fields, 2 );

    // System: Drop-down field (#16) or "locked" single-line text field (#28)
    $system = ad_materials_calc_get_entry_value( $entry_fields, 16 );

    if ( $system === '' ) {
        $system = ad_materials_calc_get_entry_value( $entry_fields, 28 );
    }

    // Pigment: Medium (#23) or Fine (#24) – whichever is filled
    $pigment_medium = ad_materials_calc_get_entry_value( $entry_fields, 23 );
    $pigment_fine   = ad_materials_calc_get_entry_value( $entry_fields, 24 );

    $pigment = '';

    if ( $pigment_medium !== '' ) {
        $pigment = $pigment_medium;
    } elseif ( $pigment_fine !== '' ) {
        $pigment = $pigment_fine;
    }

    // Interior Floor sheen: Drop-down field (#27) or "locked" single-line text field (#29)
    $sheen_interior_floor = ad_materials_calc_get_entry_value( $entry_fields, 27 );

    if ( $sheen_interior_floor === '' ) {
        $sheen_interior_floor = ad_materials_calc_get_entry_value( $entry_fields, 29 );
    }

    $answers = [
        'total_area_sqft'                     => $total_area,
        'surface_type'                        => $surface_type,
        'terrazzo_system'                     => $system,
        'pigment_color'                       => $pigment,
        'finish_color'                        => ad_materials_calc_get_entry_value( $entry_fields, 30 ),

        // Surface type option 1: Interior / Exterior Walls
        'substrate_type_wall'                 => ad_materials_calc_get_entry_value( $entry_fields, 3 ),
        'sheen_wall'                          => ad_materials_calc_get_entry_value( $entry_fields, 4 ),
        'usage_level_wall'                    => ad_materials_calc_get_entry_value( $entry_fields, 5 ),

        // Surface type option 2: Wet Area Walls
        'existing_tile_wet_area'              => ad_materials_calc_get_entry_value( $entry_fields, 6 ),
        'tile_joint_condition_wet_area'       => ad_materials_calc_get_entry_value( $entry_fields, 7 ),
        'sheen_wet_area'                      => ad_materials_calc_get_entry_value( $entry_fields, 8 ),
        'usage_level_wet_area'                => ad_materials_calc_get_entry_value( $entry_fields, 9 ),

        // Surface type option 3: Interior Floor
        'existing_tile_interior_floor'        => ad_materials_calc_get_entry_value( $entry_fields, 10 ),
        'tile_joint_condition_interior_floor' => ad_materials_calc_get_entry_value( $entry_fields, 11 ),
        'moisture_exposure_interior_floor'    => ad_materials_calc_get_entry_value( $entry_fields, 12 ),
        'usage_level_interior_floor'          => ad_materials_calc_get_entry_value( $entry_fields, 26 ),
        'sheen_interior_floor'                => $sheen_interior_floor,

        // Surface type option 4: Exterior Floor
        'existing_tile_exterior_floor'        => ad_materials_calc_get_entry_value( $entry_fields, 13 ),
        'tile_joint_condition_exterior_floor' => ad_materials_calc_get_entry_value( $entry_fields, 14 ),
        'usage_level_exterior_floor'          => ad_materials_calc_get_entry_value( $entry_fields, 15 ),
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