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

    $form_data['fields'][$field_id]['choices'] = ad_wpforms_microterrazzo_finish_choices();

    return $form_data;
}

function ad_wpforms_microterrazzo_finish_choices() {

    return [
        [
            'label' => '600Q',
            'value' => '600Q',
            'image' => '/wp-content/uploads/2026/03/600Q-Micro.png',
        ],
        [
            'label' => 'Zambia',
            'value' => 'Zambia',
            'image' => '/wp-content/uploads/2026/03/Zambia-Micro.png',
        ],
        [
            'label' => 'Roma',
            'value' => 'Roma',
            'image' => '/wp-content/uploads/2026/03/Roma-Micro.png',
        ],
    ];
}