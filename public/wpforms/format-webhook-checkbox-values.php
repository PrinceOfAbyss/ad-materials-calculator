<?php
/**
 * Format multi-choice WPForms checkbox values as arrays in webhook JSON payloads
 */

add_filter( 'wpforms_webhooks_process_delivery_request_options', 'ad_materials_calc_format_webhook_checkbox_values', 20, 5 );
function ad_materials_calc_format_webhook_checkbox_values( $options, $webhook_data, $fields, $form_data, $entry_id ) {

    if ( empty( $options['body'] ) || empty( $webhook_data['body'] ) || empty( $form_data['fields'] ) || empty( $fields ) || ! is_array( $webhook_data['body'] ) || ! is_array( $form_data['fields'] ) || ! is_array( $fields ) ) {
        return $options;
    }

    $body = is_array( $options['body'] ) ? $options['body'] : json_decode( (string) $options['body'], true );

    if ( ! is_array( $body ) ) {
        return $options;
    }

    foreach ( $body as $param_key => $param_value ) {
        if ( ! isset( $webhook_data['body'][$param_key] ) ) {
            continue;
        }

        if ( ! is_scalar( $webhook_data['body'][$param_key] ) ) {
            continue;
        }

        $field_id = absint( $webhook_data['body'][$param_key] );

        if ( ! $field_id || empty( $form_data['fields'][$field_id] ) || empty( $fields[$field_id] ) || ! ad_materials_calc_is_multi_choice_webhook_checkbox_field( $form_data['fields'][$field_id] ) ) {
            continue;
        }

        $body[$param_key] = ad_materials_calc_get_webhook_checkbox_values( $fields[$field_id] );
    }

    if ( ! empty( $options['method'] ) && $options['method'] !== 'GET' && ! empty( $webhook_data['format'] ) && $webhook_data['format'] === 'json' ) {
        $options['body'] = wp_json_encode( $body );
    } else {
        $options['body'] = $body;
    }

    return $options;
}

function ad_materials_calc_is_multi_choice_webhook_checkbox_field( array $field ) {

    if ( empty( $field['type'] ) || $field['type'] !== 'checkbox' || empty( $field['choices'] ) || ! is_array( $field['choices'] ) ) {
        return false;
    }

    return count( $field['choices'] ) > 1;
}

function ad_materials_calc_get_webhook_checkbox_values( array $field ) {

    $raw_value = array_key_exists( 'value_raw', $field ) ? $field['value_raw'] : ( $field['value'] ?? [] );

    if ( is_array( $raw_value ) ) {
        $values = $raw_value;
    } else {
        $values = preg_split( '/\s*\|\|\s*|\r\n|\r|\n/', (string) $raw_value );
    }

    $values = array_map( 'ad_materials_calc_sanitize_webhook_checkbox_value', $values );

    return array_values( array_filter( $values, 'ad_materials_calc_is_non_empty_webhook_checkbox_value' ) );
}

function ad_materials_calc_sanitize_webhook_checkbox_value( $value ) {

    return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
}

function ad_materials_calc_is_non_empty_webhook_checkbox_value( $value ) {

    return $value !== '';
}