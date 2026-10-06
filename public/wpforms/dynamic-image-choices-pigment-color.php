<?php
/**
 * Materials Calculator - Dynamic Image Choices (Pigment Color)
 *
 * Notes:
 * - Configured materials calculator form
 * - Field #23 -> Product 9838 (Medium pigments)
 * - Field #24 -> Product 9932 (Fine pigments)
 * - Field #49 -> Product 14769 (Gaia pigments)
 */

add_filter( 'wpforms_frontend_form_data', 'ad_wpforms_set_pigment_image_choices_frontend', 50, 1 );
function ad_wpforms_set_pigment_image_choices_frontend( $form_data ) {

    if ( empty( $form_data['id'] ) || absint( $form_data['id'] ) !== ad_materials_calc_get_wpforms_form_id() ) {
        return $form_data;
    }

    return ad_wpforms_inject_pigment_image_choices( $form_data );
}

add_filter( 'wpforms_process_before_form_data', 'ad_wpforms_set_pigment_image_choices_process', 50, 2 );
function ad_wpforms_set_pigment_image_choices_process( $form_data, $entry ) {

    if ( empty( $form_data['id'] ) || absint( $form_data['id'] ) !== ad_materials_calc_get_wpforms_form_id() ) {
        return $form_data;
    }

    return ad_wpforms_inject_pigment_image_choices( $form_data );
}

function ad_wpforms_inject_pigment_image_choices( $form_data ) {

    if ( ! function_exists( 'wc_get_product' ) ) {
        return $form_data;
    }

    $field_product_map = [
        23 => 9838, // Medium pigments
        24 => 9932, // Fine pigments
        49 => 14769, // Gaia pigments
    ];

    foreach ( $field_product_map as $field_id => $product_id ) {
        if ( empty( $form_data['fields'][$field_id] ) ) {
            continue;
        }

        $type = isset( $form_data['fields'][$field_id]['type'] ) ? (string) $form_data['fields'][$field_id]['type'] : '';

        if ( $type !== 'radio' ) {
            continue;
        }

        $product = wc_get_product( (int) $product_id );

        if ( ! $product || ! $product->is_type( 'variable' ) ) {
            continue;
        }

        $choices = ad_wpforms_build_image_choices_from_product( $product );

        // Prepend "Unpigmented" option
        array_unshift( $choices, ad_wpforms_unpigmented_choice() );

        $form_data['fields'][$field_id]['choices'] = $choices;
    }

    return $form_data;
}

function ad_wpforms_unpigmented_choice() {

    $img_url = trailingslashit( wp_get_upload_dir()['baseurl'] ) . ltrim( '2026/10/swatch-unpigmented-finish.webp', '/' );

    return [
        'label' => 'Unpigmented',
        'value' => '0',
        'image' => $img_url,
    ];
}

function ad_wpforms_build_image_choices_from_product( WC_Product_Variable $product ) {

    $variations = $product->get_available_variations();

    if ( empty( $variations ) ) {
        return [];
    }

    $choices = [];

    foreach ( $variations as $variation_data ) {
        $variation_id = isset( $variation_data['variation_id'] ) ? (int) $variation_data['variation_id'] : 0;

        if ( ! $variation_id ) {
            continue;
        }

        $variation = wc_get_product( $variation_id );

        if ( ! $variation ) {
            continue;
        }

        if ( method_exists( $variation, 'is_purchasable' ) && ! $variation->is_purchasable() ) {
            continue;
        }

        $label = ad_wpforms_variation_color_label( $variation );

        if ( $label === '' ) {
            continue;
        }

        $image_url = ad_wpforms_variation_image_url( $variation );

        if ( $image_url === '' ) {
            continue;
        }

        $choices[] = [
            'label' => $label,
            'value' => (string) $variation->get_id(),
            'image' => $image_url,
        ];
    }

    return $choices;
}

function ad_wpforms_variation_color_label( WC_Product_Variation $variation ) {

    $color_slug = $variation->get_attribute( 'pa_color' );

    if ( empty( $color_slug ) ) {
        return '';
    }

    $term = get_term_by( 'slug', $color_slug, 'pa_color' );

    if ( $term && ! is_wp_error( $term ) ) {
        return $term->name;
    }

    return (string) $color_slug;
}

function ad_wpforms_variation_image_url( WC_Product_Variation $variation ) {

    $image_id = (int) $variation->get_image_id();

    if ( ! $image_id ) {
        return '';
    }

    $url = wp_get_attachment_image_url( $image_id, 'thumbnail' );

    return $url ? $url : '';
}