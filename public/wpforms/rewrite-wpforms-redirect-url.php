<?php
/**
 * Materials Calculator - Rewrite WPForms Redirect URL
 * Set WPForms confirmation redirect to the site's URL + a param to signal the form's submit
 */

add_filter( 'wpforms_process_redirect_url', 'ad_materials_calc_rewrite_wpforms_redirect_url', 10, 3 );
function ad_materials_calc_rewrite_wpforms_redirect_url( $url, $form_id, $fields ) {

    if ( absint( $form_id ) !== ad_materials_calc_get_wpforms_form_id() ) {
        return $url;
    }

    return home_url( '/materials-calculator/' ) . '?mc=1';
}