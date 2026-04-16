<?php
/**
 * Materials Calculator - WPForms smart tag for discount field visibility
 *
 * Notes:
 * - Registers a smart tag for the calculator form
 * - Resolves the tag to "show" for privileged staff members
 * - Resolves the tag to "hide" for all other users
 */

add_filter( 'wpforms_smart_tags', 'ad_materials_calc_register_show_calculator_discount_smart_tag' );
function ad_materials_calc_register_show_calculator_discount_smart_tag( $tags ) {

    $tags['show_calculator_discount'] = 'Show Calculator Discount';

    return $tags;
}

add_filter( 'wpforms_smart_tag_process', 'ad_materials_calc_process_show_calculator_discount_smart_tag', 10, 2 );
function ad_materials_calc_process_show_calculator_discount_smart_tag( $content, $tag ) {

    if ( 'show_calculator_discount' !== $tag ) {
        return $content;
    }

    $visibility = ad_materials_calc_current_user_has_extended_privileges() ? 'show' : 'hide';

    return str_replace( '{show_calculator_discount}', $visibility, $content );
}