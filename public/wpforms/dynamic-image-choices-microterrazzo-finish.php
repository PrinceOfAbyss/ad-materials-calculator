<?php
/**
 * Materials Calculator - Dynamic Image Choices (Microterrazzo Finish)
 *
 * Notes:
 * - Configured materials calculator form
 * - Field #30 -> Microterrazzo finish choices
 */

add_filter( 'wpforms_frontend_form_data', 'ad_wpforms_set_microterrazzo_finish_image_choices_frontend', 50, 1 );
function ad_wpforms_set_microterrazzo_finish_image_choices_frontend( $form_data ) {

    if ( empty( $form_data['id'] ) || absint( $form_data['id'] ) !== ad_materials_calc_get_wpforms_form_id() ) {
        return $form_data;
    }

    return ad_wpforms_inject_microterrazzo_finish_image_choices( $form_data );
}

add_filter( 'wpforms_process_before_form_data', 'ad_wpforms_set_microterrazzo_finish_image_choices_process', 50, 2 );
function ad_wpforms_set_microterrazzo_finish_image_choices_process( $form_data, $entry ) {

    if ( empty( $form_data['id'] ) || absint( $form_data['id'] ) !== ad_materials_calc_get_wpforms_form_id() ) {
        return $form_data;
    }

    return ad_wpforms_inject_microterrazzo_finish_image_choices( $form_data );
}

function ad_wpforms_inject_microterrazzo_finish_image_choices( $form_data ) {

    $field_id = 30;

    if ( empty( $form_data['fields'][$field_id] ) ) {
        return $form_data;
    }

    $type = isset( $form_data['fields'][$field_id]['type'] ) ? (string) $form_data['fields'][$field_id]['type'] : '';

    if ( $type !== 'radio' ) {
        return $form_data;
    }

    $choices = ad_wpforms_microterrazzo_finish_choices();

    if ( ! empty( $choices ) ) {
        $form_data['fields'][$field_id]['choices'] = $choices;
    }

    return $form_data;
}

function ad_wpforms_microterrazzo_finish_choices() {

    $terms = wc_get_product_terms(
        10068,
        'pa_finish',
        [
            'orderby' => 'menu_order',
            'order'   => 'ASC',
        ]
    );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return [];
    }

    $choices = [];

    foreach ( $terms as $term ) {
        $image      = get_term_meta( $term->term_id, 'image', true );
        $image_id   = is_array( $image ) ? absint( $image['id'] ?? 0 ) : 0;
        $stored_url = is_array( $image ) ? (string) ( $image['url'] ?? '' ) : ( is_string( $image ) ? $image : '' );

        if ( ! $image_id && $stored_url ) {
            $image_id = attachment_url_to_postid( $stored_url );
        }

        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';

        if ( ! $image_url ) {
            continue;
        }

        $choices[] = [
            'label' => $term->name,
            'value' => $term->name,
            'image' => $image_url,
        ];
    }

    return $choices;
}