<?php
/**
 * Materials Calculator - Make the "locked" Terrazzo system text field read-only
 */

add_action( 'wp_footer', 'ad_materials_calc_make_system_readonly' );
function ad_materials_calc_make_system_readonly() {

    if ( ! is_page( 11328 ) ) {
        return;
    }
    ?>
	<script>
	jQuery(function($){
		$('.ad-readonly-faux-locked-field input').prop('readonly', true);
	});
	</script>
    <?php
}