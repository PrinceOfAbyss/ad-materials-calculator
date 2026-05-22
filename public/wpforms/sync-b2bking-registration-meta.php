<?php
/**
 * Sync WPForms registration submissions into B2BKing's usermeta shape.
 *
 * B2BKing expects a very specific meta structure for registration data,
 * especially for checkbox fields, so we keep the full mapping in code instead
 * of splitting responsibility between WPForms' generic usermeta UI and hooks.
 */

// Inject a temporary username field so WPForms validates + emails without exposing a real form field
add_filter( 'wpforms_process_before_form_data', 'ad_materials_calc_add_wpforms_registration_username_field', 20, 2 );
function ad_materials_calc_add_wpforms_registration_username_field( $form_data, $entry ) {

    $form_id = ! empty( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
    $configs = ad_materials_calc_get_b2bking_registration_sync_configs();

    if ( ! isset( $configs[$form_id] ) ) {
        return $form_data;
    }

    $username_field_id = ad_materials_calc_get_wpforms_registration_username_field_id( $form_id );

    $form_data['fields'][$username_field_id] = [
        'id'    => $username_field_id,
        'type'  => 'hidden',
        'label' => 'Registration Username',
        'meta'  => [
            'nickname' => 'username',
        ],
    ];

    $form_data['settings']['registration_username'] = $username_field_id;

    return $form_data;
}

// Populate the temporary username before WPForms User Registration validates and creates the user
add_filter( 'wpforms_process_filter', 'ad_materials_calc_populate_wpforms_registration_username', 5, 3 );
function ad_materials_calc_populate_wpforms_registration_username( $fields, $entry, $form_data ) {

    $form_id        = ! empty( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
    $configs        = ad_materials_calc_get_b2bking_registration_sync_configs();
    $email_field_id = absint( $form_data['settings']['registration_email'] ?? 0 );
    $email          = ! empty( $fields[$email_field_id]['value'] ) ? trim( (string) $fields[$email_field_id]['value'] ) : '';

    if ( ! isset( $configs[$form_id] ) || ! $email_field_id || $email === '' ) {
        return $fields;
    }

    $username_field_id = ad_materials_calc_get_wpforms_registration_username_field_id( $form_id );
    $username          = ad_materials_calc_generate_registration_username_from_email( $email );

    $fields[$username_field_id] = [
        'id'        => $username_field_id,
        'type'      => 'hidden',
        'name'      => 'Registration Username',
        'value'     => $username,
        'value_raw' => $username,
    ];

    return $fields;
}

// Remove the virtual username before entries are saved or emailed to admins
add_filter( 'wpforms_entry_save_data', 'ad_materials_calc_remove_wpforms_registration_username_field_from_output', 20, 3 );
add_filter( 'wpforms_entry_email_data', 'ad_materials_calc_remove_wpforms_registration_username_field_from_output', 20, 3 );
function ad_materials_calc_remove_wpforms_registration_username_field_from_output( $fields, $entry, $form_data ) {

    $form_id = ! empty( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
    $configs = ad_materials_calc_get_b2bking_registration_sync_configs();

    if ( ! isset( $configs[$form_id] ) ) {
        return $fields;
    }

    unset( $fields[ad_materials_calc_get_wpforms_registration_username_field_id( $form_id )] );

    return $fields;
}

// Swap generic WPForms activation links for our partner login and reset URLs
add_filter( 'wpforms_user_registration_email_notifications_after_activation_user_email', 'ad_materials_calc_replace_wpforms_activation_email_urls' );
function ad_materials_calc_replace_wpforms_activation_email_urls( $email ) {

    if ( empty( $email['user'] ) || ! is_a( $email['user'], 'WP_User' ) || empty( $email['message'] ) || ! is_string( $email['message'] ) ) {
        return $email;
    }

    if ( get_user_meta( $email['user']->ID, 'b2bking_registration_data_saved', true ) !== 'yes' ) {
        return $email;
    }

    $email['message'] = str_replace(
        [
            '{user_registration_password_reset}',
            '{url_login}',
        ],
        [
            ad_materials_calc_get_partner_password_reset_url( $email['user'] ),
            home_url( '/login/' ),
        ],
        $email['message']
    );

    return $email;
}

// Normalize WPForms' generated reset URL before the query string is appended
add_filter( 'wpforms_smarttags_process_user_registration_password_reset_value', 'ad_materials_calc_trailingslash_wpforms_reset_password_url', 10, 6 );
function ad_materials_calc_trailingslash_wpforms_reset_password_url( $url, $form_data, $fields, $entry_id, $smart_tag_object, $context ) {

    if ( empty( $form_data['meta']['template'] ) || $form_data['meta']['template'] !== 'user_reset' ) {
        return $url;
    }

    $url          = (string) $url;
    $query_pos    = strpos( $url, '?' );
    $fragment_pos = strpos( $url, '#' );
    $split_pos    = false;

    if ( $query_pos !== false && $fragment_pos !== false ) {
        $split_pos = min( $query_pos, $fragment_pos );
    } elseif ( $query_pos !== false ) {
        $split_pos = $query_pos;
    } elseif ( $fragment_pos !== false ) {
        $split_pos = $fragment_pos;
    }

    if ( $split_pos === false ) {
        return trailingslashit( $url );
    }

    return trailingslashit( substr( $url, 0, $split_pos ) ) . substr( $url, $split_pos );
}

// Restore the reset form title divider after WPForms switches into password-entry mode
add_filter( 'wpforms_frontend_form_data', 'ad_materials_calc_restore_wpforms_reset_password_divider', 1000 );
function ad_materials_calc_restore_wpforms_reset_password_divider( $form_data ) {

    if ( empty( $form_data['meta']['template'] ) || $form_data['meta']['template'] !== 'user_reset' || empty( $form_data['id'] ) ) {
        return $form_data;
    }

    if ( ! class_exists( '\WPFormsUserRegistration\Process\Helpers\UserReset' ) || ! \WPFormsUserRegistration\Process\Helpers\UserReset::is_reset_in_progress() || ! \WPFormsUserRegistration\Process\Helpers\UserReset::get_user() ) {
        return $form_data;
    }

    foreach ( $form_data['fields'] as $field ) {
        if ( ! empty( $field['type'] ) && $field['type'] === 'divider' ) {
            return $form_data;
        }
    }

    $form_obj        = wpforms()->obj( 'form' );
    $saved_form_data = $form_obj ? $form_obj->get( absint( $form_data['id'] ), ['content_only' => true] ) : [];

    if ( empty( $saved_form_data['fields'] ) || ! is_array( $saved_form_data['fields'] ) ) {
        return $form_data;
    }

    foreach ( $saved_form_data['fields'] as $field_id => $field ) {
        if ( empty( $field['type'] ) || $field['type'] !== 'divider' ) {
            continue;
        }

        $form_data['fields'] = [$field_id => $field] + $form_data['fields'];
        break;
    }

    return $form_data;
}

// Rename WordPress password-change admin emails for synced partner users
add_filter( 'wp_password_change_notification_email', 'ad_materials_calc_rename_partner_password_change_email', 10, 3 );
function ad_materials_calc_rename_partner_password_change_email( $email, $user, $blogname ) {

    if ( ! is_a( $user, 'WP_User' ) || get_user_meta( $user->ID, 'b2bking_registration_data_saved', true ) !== 'yes' ) {
        return $email;
    }

    $email['subject'] = sprintf( __( '[%s] Your password was reset', 'ad-materials-calculator' ), $blogname );
    $email['message'] = sprintf( __( 'Password successfully set or changed for user: %s', 'ad-materials-calculator' ), $user->user_email );

    return $email;
}

// Remove WPForms approval row links from the Users screen
add_filter( 'user_row_actions', 'ad_materials_calc_hide_wpforms_user_approval_row_actions', 100, 2 );
add_filter( 'ms_user_row_actions', 'ad_materials_calc_hide_wpforms_user_approval_row_actions', 100, 2 );
function ad_materials_calc_hide_wpforms_user_approval_row_actions( $actions, $user ) {

    unset( $actions['wpforms-approve'], $actions['wpforms-unapprove'] );

    return $actions;
}

// Copy approved WPForms registration answers into the B2BKing registration meta format
add_action( 'wpforms_user_registration_process_registration_process_completed_after', 'ad_materials_calc_sync_b2bking_registration_meta', 10, 4 );
function ad_materials_calc_sync_b2bking_registration_meta( $user_id, $fields, $form_data, $user_data ) {

    $form_id = ! empty( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
    $configs = ad_materials_calc_get_b2bking_registration_sync_configs();

    if ( ! isset( $configs[$form_id] ) ) {
        return;
    }

    $config = $configs[$form_id];

    ad_materials_calc_clear_b2bking_registration_meta( $user_id );

    update_user_meta( $user_id, 'b2bking_registration_data_saved', 'yes' );
    update_user_meta( $user_id, 'b2bking_registration_role', $config['registration_role'] );
    update_user_meta( $user_id, 'b2bking_account_approved', 'no' );
    update_user_meta( $user_id, 'b2bking_default_approval_manual', $config['default_approval_manual'] );

    $b2bking_field_ids = [];

    foreach ( $config['fields'] as $b2bking_field_id => $field_config ) {
        $b2bking_field_ids[] = (int) $b2bking_field_id;
        ad_materials_calc_sync_b2bking_registration_field( $user_id, (int) $b2bking_field_id, $field_config, $fields );
    }

    update_user_meta( $user_id, 'b2bking_custom_fields_string', implode( ',', $b2bking_field_ids ) );
}

// When B2BKing approves a synced applicant, finish the matching WPForms approval state too
add_action( 'added_user_meta', 'ad_materials_calc_maybe_finish_wpforms_approval_after_b2bking', 10, 4 );
add_action( 'updated_user_meta', 'ad_materials_calc_maybe_finish_wpforms_approval_after_b2bking', 10, 4 );
function ad_materials_calc_maybe_finish_wpforms_approval_after_b2bking( $meta_id, $user_id, $meta_key, $meta_value ) {

    if ( $meta_key !== 'b2bking_account_approved' || $meta_value !== 'yes' ) {
        return;
    }

    if ( get_user_meta( $user_id, 'b2bking_registration_data_saved', true ) !== 'yes' ) {
        return;
    }

    if ( ! get_user_meta( $user_id, 'wpforms-pending', true ) ) {
        return;
    }

    delete_user_meta( $user_id, 'wpforms-pending' );
    delete_user_meta( $user_id, 'wpforms-confirmation' );

    if ( class_exists( '\WPFormsUserRegistration\Helper' ) ) {
        \WPFormsUserRegistration\Helper::set_user_role( absint( $user_id ) );
    }

    if ( function_exists( 'wpforms_user_registration' ) && is_object( wpforms_user_registration() ) ) {
        $email_notifications = wpforms_user_registration()->get( 'email_notifications' );

        if ( is_object( $email_notifications ) && is_callable( [$email_notifications, 'after_activation'] ) ) {
            $email_notifications->after_activation( $user_id );
        }
    }

    do_action( 'wpforms_user_registration_admin_users_listing_do_approve_after', $user_id );
}

// Send synced partner users back to the partner login after logout
add_filter( 'logout_redirect', 'ad_materials_calc_redirect_partner_logout_to_partner_login', 10, 3 );
function ad_materials_calc_redirect_partner_logout_to_partner_login( $redirect_to, $requested_redirect_to, $user ) {

    if ( ! is_a( $user, 'WP_User' ) ) {
        return $redirect_to;
    }

    if ( get_user_meta( $user->ID, 'b2bking_registration_data_saved', true ) !== 'yes' ) {
        return $redirect_to;
    }

    return home_url( '/login/' );
}

// Bypass WP Rocket cache for the stateful partner password reset URL
add_filter( 'rocket_cache_reject_uri', 'ad_materials_calc_exclude_partner_password_reset_from_wp_rocket_cache' );
function ad_materials_calc_exclude_partner_password_reset_from_wp_rocket_cache( $uris ) {

    $uris[] = '/password-reset(.*)';

    return array_values( array_unique( $uris ) );
}

// Bypass WP Rocket cache when WPForms carries an active reset token cookie
add_filter( 'rocket_cache_reject_cookies', 'ad_materials_calc_exclude_wpforms_reset_cookie_from_wp_rocket_cache' );
function ad_materials_calc_exclude_wpforms_reset_cookie_from_wp_rocket_cache( $cookies ) {

    $cookies[] = 'wpforms-resetpass-';

    return array_values( array_unique( $cookies ) );
}

// Build the direct WPForms password reset URL for a synced partner user
function ad_materials_calc_get_partner_password_reset_url( WP_User $user ) {

    $reset_page_url = home_url( '/password-reset/' );
    $reset_key      = get_password_reset_key( $user );

    if ( is_wp_error( $reset_key ) ) {
        return $reset_page_url;
    }

    return add_query_arg(
        [
            'action' => 'wpforms_rp',
            'key'    => $reset_key,
            'login'  => rawurlencode( $user->user_login ),
        ],
        $reset_page_url
    );
}

// Use a synthetic field ID that cannot collide with real WPForms field IDs
function ad_materials_calc_get_wpforms_registration_username_field_id( $form_id ) {

    return 900000 + absint( $form_id );
}

// Generate a WordPress-safe username while preserving + aliases as readable hyphenated addresses
function ad_materials_calc_generate_registration_username_from_email( $email ) {

    $base = strtolower( str_replace( '+', '-', trim( (string) $email ) ) );
    $base = sanitize_user( $base, true );

    if ( $base === '' ) {
        $base = 'user';
    }

    $username = $base;
    $suffix   = 2;

    while ( username_exists( $username ) ) {
        if ( strpos( $base, '@' ) !== false ) {
            $username = preg_replace( '/@([^@]+)$/', '-' . $suffix . '@$1', $base );
        } else {
            $username = $base . '-' . $suffix;
        }

        $suffix++;
    }

    return $username;
}

// Return the B2BKing field mapping for the configured applicator and distributor forms
function ad_materials_calc_get_b2bking_registration_sync_configs() {

    $applicator_form_id  = ad_materials_calc_get_applicator_registration_form_id();
    $distributor_form_id = ad_materials_calc_get_distributor_registration_form_id();

    if ( ! $applicator_form_id && ! $distributor_form_id ) {
        return [];
    }

    $common_name_fields = [
        12302 => [
            'type'       => 'name_part',
            'field_id'   => 5,
            'label'      => 'Name',
            'part'       => 'first',
            'wp_userkey' => 'first_name',
        ],
        12303 => [
            'type'       => 'name_part',
            'field_id'   => 5,
            'label'      => 'Name',
            'part'       => 'last',
            'wp_userkey' => 'last_name',
        ],
        12309 => [
            'type'     => 'text',
            'field_id' => 18,
            'label'    => 'Phone Number',
        ],
    ];

    $configs = [];

    if ( $applicator_form_id ) {
        $configs[$applicator_form_id] = [
            'registration_role'       => 'role_12301',
            'default_approval_manual' => 'group_12313',
            'fields'                  => $common_name_fields + [
                12356 => [
                    'type'     => 'text',
                    'field_id' => 20,
                    'label'    => 'LLC / Business Name',
                ],
                12355 => [
                    'type'     => 'text',
                    'field_id' => 24,
                    'label'    => 'State of Registration (LLC)',
                ],
                12359 => [
                    'type'     => 'text',
                    'field_id' => 25,
                    'label'    => 'Years in Business',
                ],
                12430 => [
                    'type'     => 'text',
                    'field_id' => 21,
                    'label'    => 'Business Website',
                ],
                12434 => [
                    'type'     => 'checkbox',
                    'field_id' => 28,
                    'label'    => 'Primary Finish Types Applied',
                    'options'  => [
                        1 => 'Microcement / Microtopping',
                        2 => 'Venetian Plaster',
                        3 => 'Limewash',
                        4 => 'Epoxy Flooring',
                        5 => 'Concrete / GFRC',
                        6 => 'Other Decorative Finishes',
                    ],
                ],
                12436 => [
                    'type'     => 'checkbox',
                    'field_id' => 29,
                    'label'    => 'Primary Project Types',
                    'options'  => [
                        1 => 'Residential',
                        2 => 'Commercial',
                        3 => 'Hospitality',
                        4 => 'Retail',
                    ],
                ],
                12432 => [
                    'type'     => 'text',
                    'field_id' => 30,
                    'label'    => 'Primary Market(s) Served',
                ],
                12442 => [
                    'type'     => 'text',
                    'field_id' => 31,
                    'label'    => 'Current or Past Products Used',
                ],
                12444 => [
                    'type'     => 'text',
                    'field_id' => 32,
                    'label'    => 'Approximate Monthly Volume (sq ft)',
                ],
                12452 => [
                    'type'     => 'text',
                    'field_id' => 33,
                    'label'    => "Anything else you'd like us to know?",
                ],
                12766 => [
                    'type'     => 'file',
                    'field_id' => 37,
                    'label'    => 'Tax Exemption Certificate',
                ],
            ],
        ];
    }

    if ( $distributor_form_id ) {
        $configs[$distributor_form_id] = [
            'registration_role'       => 'role_12336',
            'default_approval_manual' => 'group_12314',
            'fields'                  => $common_name_fields + [
                12353 => [
                    'type'     => 'text',
                    'field_id' => 34,
                    'label'    => 'Title / Role',
                ],
                12358 => [
                    'type'     => 'text',
                    'field_id' => 21,
                    'label'    => 'Company Website',
                ],
                12354 => [
                    'type'     => 'text',
                    'field_id' => 20,
                    'label'    => 'Company / Showroom Name',
                ],
                12360 => [
                    'type'     => 'text',
                    'field_id' => 24,
                    'label'    => 'Number of Locations',
                ],
                12361 => [
                    'type'     => 'text',
                    'field_id' => 35,
                    'label'    => 'Primary Showroom Address',
                ],
                12431 => [
                    'type'     => 'text',
                    'field_id' => 37,
                    'label'    => 'Showroom Size (sq ft)',
                ],
                12433 => [
                    'type'     => 'text',
                    'field_id' => 38,
                    'label'    => 'Primary City / Region Served',
                ],
                12435 => [
                    'type'     => 'checkbox',
                    'field_id' => 28,
                    'label'    => 'Product Categories Currently Carried',
                    'options'  => [
                        1  => 'Plasters / Venetian Plaster',
                        2  => 'Microcement / Coatings',
                        3  => 'Tile / Stone',
                        4  => 'Flooring',
                        5  => 'Furniture',
                        6  => 'Lighting',
                        7  => 'Hardware / Fixtures',
                        8  => 'Textiles / Wallcoverings',
                        9  => 'Paints / Primers',
                        10 => 'Other',
                    ],
                ],
                12443 => [
                    'type'     => 'text',
                    'field_id' => 39,
                    'label'    => 'Specific Brands / Product Lines Currently Sold',
                ],
                12445 => [
                    'type'     => 'text',
                    'field_id' => 41,
                    'label'    => 'Sales Model',
                ],
                12446 => [
                    'type'     => 'text',
                    'field_id' => 30,
                    'label'    => 'Approximate B2B / B2C Split',
                ],
                12447 => [
                    'type'     => 'text',
                    'field_id' => 43,
                    'label'    => 'Do you have existing relationships with interior designers or architects?',
                ],
                12448 => [
                    'type'     => 'text',
                    'field_id' => 31,
                    'label'    => 'Key Design / Architecture Clients or Firms (if applicable)',
                ],
                12449 => [
                    'type'     => 'checkbox',
                    'field_id' => 44,
                    'label'    => 'Types of Projects Your Clients Typically Specify For',
                    'options'  => [
                        1 => 'Luxury Residential',
                        2 => 'Multi-Family / Residential Tower',
                        3 => 'Hospitality / Hotels',
                        4 => 'Retail / Commercial',
                        5 => 'Office / Corporate',
                        6 => 'Healthcare / Wellness',
                    ],
                ],
                12450 => [
                    'type'     => 'text',
                    'field_id' => 46,
                    'label'    => 'Expected Monthly Purchase Volume',
                ],
                12451 => [
                    'type'     => 'text',
                    'field_id' => 47,
                    'label'    => 'Do you have trained applicators in your network?',
                ],
                12453 => [
                    'type'     => 'text',
                    'field_id' => 33,
                    'label'    => 'What draws you to Terrazzo USA? / Anything else we should know?',
                ],
                12766 => [
                    'type'     => 'file',
                    'field_id' => 51,
                    'label'    => 'Tax Exemption Certificate',
                ],
            ],
        ];
    }

    return $configs;
}

// Read the configured WPForms applicator registration form ID
function ad_materials_calc_get_applicator_registration_form_id() {

    return absint( get_option( 'ad_materials_calculator_applicator_registration_form_id', 0 ) );
}

// Read the configured WPForms distributor registration form ID
function ad_materials_calc_get_distributor_registration_form_id() {

    return absint( get_option( 'ad_materials_calculator_distributor_registration_form_id', 0 ) );
}

// Sync one mapped WPForms field into B2BKing's expected usermeta key
function ad_materials_calc_sync_b2bking_registration_field( $user_id, $b2bking_field_id, array $field_config, array $fields ) {

    if ( $field_config['type'] === 'name_part' ) {
        $value = ad_materials_calc_get_wpforms_name_part( $fields, $field_config['field_id'], $field_config['part'] );

        if ( $value !== '' ) {
            update_user_meta( $user_id, $field_config['wp_userkey'], $value );
        }

        update_user_meta( $user_id, 'b2bking_custom_field_' . $b2bking_field_id, $value );
        return;
    }

    if ( $field_config['type'] === 'checkbox' ) {
        ad_materials_calc_sync_b2bking_checkbox_field( $user_id, $b2bking_field_id, $field_config, $fields );
        return;
    }

    if ( $field_config['type'] === 'file' ) {
        $attachment_id = ad_materials_calc_get_wpforms_attachment_id( $fields, $field_config['field_id'] );

        if ( $attachment_id > 0 ) {
            update_user_meta( $user_id, 'b2bking_custom_field_' . $b2bking_field_id, $attachment_id );
        }

        return;
    }

    $value = ad_materials_calc_get_wpforms_value( $fields, $field_config['field_id'] );

    update_user_meta( $user_id, 'b2bking_custom_field_' . $b2bking_field_id, $value );
}

// Store selected checkbox labels using B2BKing's per-option usermeta shape
function ad_materials_calc_sync_b2bking_checkbox_field( $user_id, $b2bking_field_id, array $field_config, array $fields ) {

    $selected_labels = ad_materials_calc_get_wpforms_selected_labels( $fields, $field_config['field_id'] );

    if ( empty( $selected_labels ) ) {
        return;
    }

    update_user_meta( $user_id, 'b2bking_custom_field_' . $b2bking_field_id, 1 );

    $normalized_selected = array_map( 'ad_materials_calc_normalize_wpforms_label', $selected_labels );

    foreach ( $field_config['options'] as $option_index => $option_label ) {
        if ( in_array( ad_materials_calc_normalize_wpforms_label( $option_label ), $normalized_selected, true ) ) {
            update_user_meta( $user_id, 'b2bking_custom_field_' . $b2bking_field_id . '_option_' . $option_index, $option_label );
        }
    }
}

// Remove previously synced B2BKing registration values before writing a fresh submission
function ad_materials_calc_clear_b2bking_registration_meta( $user_id ) {

    $configs   = ad_materials_calc_get_b2bking_registration_sync_configs();
    $field_ids = [];

    foreach ( $configs as $config ) {
        $field_ids = array_merge( $field_ids, array_keys( $config['fields'] ) );
    }

    foreach ( array_unique( array_map( 'absint', $field_ids ) ) as $field_id ) {
        delete_user_meta( $user_id, 'b2bking_custom_field_' . $field_id );

        for ( $i = 1; $i <= 20; $i++ ) {
            delete_user_meta( $user_id, 'b2bking_custom_field_' . $field_id . '_option_' . $i );
        }
    }
}

// Read and sanitize a scalar WPForms field value
function ad_materials_calc_get_wpforms_value( array $fields, $field_id ) {

    $field = ad_materials_calc_get_wpforms_field( $fields, $field_id );

    if ( empty( $field ) ) {
        return '';
    }

    if ( isset( $field['value'] ) ) {
        return ad_materials_calc_sanitize_b2bking_registration_value( $field['value'] );
    }

    return '';
}

// Extract first or last name from WPForms' name field structure
function ad_materials_calc_get_wpforms_name_part( array $fields, $field_id, $part ) {

    $field = ad_materials_calc_get_wpforms_field( $fields, $field_id );

    if ( empty( $field ) ) {
        return '';
    }

    if ( isset( $field[$part] ) ) {
        return ad_materials_calc_sanitize_b2bking_registration_value( $field[$part] );
    }

    if ( empty( $field['value'] ) ) {
        return '';
    }

    $name_parts = preg_split( '/\s+/', trim( (string) $field['value'] ) );

    if ( $part === 'first' ) {
        return ad_materials_calc_sanitize_b2bking_registration_value( reset( $name_parts ) );
    }

    if ( count( $name_parts ) <= 1 ) {
        return '';
    }

    array_shift( $name_parts );

    return ad_materials_calc_sanitize_b2bking_registration_value( implode( ' ', $name_parts ) );
}

// Normalize selected WPForms checkbox labels into a comparable list
function ad_materials_calc_get_wpforms_selected_labels( array $fields, $field_id ) {

    $field = ad_materials_calc_get_wpforms_field( $fields, $field_id );

    if ( empty( $field ) ) {
        return [];
    }

    $raw_value = isset( $field['value_raw'] ) ? $field['value_raw'] : ( isset( $field['value'] ) ? $field['value'] : '' );

    if ( is_array( $raw_value ) ) {
        $values = $raw_value;
    } else {
        $values = preg_split( '/\r\n|\r|\n|,/', (string) $raw_value );
    }

    return array_values( array_filter( array_map( 'ad_materials_calc_sanitize_b2bking_registration_value', $values ) ) );
}

// Extract the Media Library attachment ID from a WPForms file upload field
function ad_materials_calc_get_wpforms_attachment_id( array $fields, $field_id ) {

    $field = ad_materials_calc_get_wpforms_field( $fields, $field_id );

    if ( empty( $field ) ) {
        return 0;
    }

    if ( ! empty( $field['value_raw'][0]['attachment_id'] ) ) {
        return absint( $field['value_raw'][0]['attachment_id'] );
    }

    return 0;
}

// Safely fetch a submitted WPForms field array by field ID
function ad_materials_calc_get_wpforms_field( array $fields, $field_id ) {

    $field_id = absint( $field_id );

    if ( isset( $fields[$field_id] ) && is_array( $fields[$field_id] ) ) {
        return $fields[$field_id];
    }

    return [];
}

// Normalize labels so WPForms choices can be matched against B2BKing options
function ad_materials_calc_normalize_wpforms_label( $label ) {

    $label = wp_strip_all_tags( (string) $label );
    $label = strtolower( trim( preg_replace( '/\s+/', ' ', $label ) ) );

    return $label;
}

// Sanitize values before storing them in B2BKing usermeta
function ad_materials_calc_sanitize_b2bking_registration_value( $value ) {

    if ( is_array( $value ) ) {
        $value = implode( ', ', array_map( 'sanitize_text_field', $value ) );
    }

    return sanitize_textarea_field( (string) $value );
}