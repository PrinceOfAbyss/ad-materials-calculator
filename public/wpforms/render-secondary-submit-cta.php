<?php
/**
 * Add the secondary CTA after the Materials Calculator submit button.
 */

add_action( 'wpforms_display_submit_after', 'ad_materials_calc_render_secondary_submit_cta', 10, 2 );
function ad_materials_calc_render_secondary_submit_cta( $form_data, $button ) {

    if ( 'submit' !== $button || empty( $form_data['id'] ) || absint( $form_data['id'] ) !== ad_materials_calc_get_wpforms_form_id() ) {
        return;
    }

    $cta_url = home_url( '/application-services/' ) . '#inquiry_form';

    echo '<a class="ad-calculator-secondary-link wpforms-submit" href="' . esc_url( $cta_url ) . '">' . esc_html__( 'Planning a larger or more complex project? Contact Our Studio', 'ad-materials-calculator' ) . '</a>';
}