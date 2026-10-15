<?php
/**
 * Materials Calculator - Preselect Finish From Current Page
 */

add_filter( 'wpforms_field_data', 'ad_wpforms_preselect_finish_from_current_page', 10, 2 );
function ad_wpforms_preselect_finish_from_current_page( $field, $form_data ) {

    if ( empty( $form_data['id'] ) || absint( $form_data['id'] ) !== ad_materials_calc_get_wpforms_form_id() ) {
        return $field;
    }

    if ( empty( $field['id'] ) || 16 !== absint( $field['id'] ) || empty( $field['choices'] ) || ! is_array( $field['choices'] ) ) {
        return $field;
    }

    $page_id = get_queried_object_id();

    if ( ! $page_id ) {
        return $field;
    }

    if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
        $default_language = apply_filters( 'wpml_default_language', null );
        $default_page_id  = apply_filters( 'wpml_object_id', $page_id, 'page', true, $default_language );

        if ( $default_page_id ) {
            $page_id = $default_page_id;
        }
    }

    $page_slug = get_post_field( 'post_name', $page_id );

    $finish_pages = [
        'medium'                                        => 1,
        'fine'                                          => 2,
        'microterrazzo'                                 => 3,
        'gaia-a-wet-area-friendly-limewash-alternative' => 4,
        'monolith'                                      => 5,
        'micromosaic'                                   => 6,
    ];

    if ( ! isset( $finish_pages[$page_slug] ) ) {
        return $field;
    }

    $selected_choice = $finish_pages[$page_slug];

    if ( ! isset( $field['choices'][$selected_choice] ) ) {
        return $field;
    }

    foreach ( $field['choices'] as $choice_id => &$choice ) {
        unset( $choice['default'] );

        if ( (int) $choice_id === $selected_choice ) {
            $choice['default'] = '1';
        }
    }

    unset( $choice );

    return $field;
}