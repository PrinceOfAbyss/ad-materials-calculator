<?php
/**
 * AJAX handlers, frontend helpers and result/cart rendering for area calculators
 *
 * @package Ad_Materials_Calculator
 */

add_action( 'wp_ajax_ad_materials_calc_area_calculate', 'ad_materials_calc_ajax_area_calculate' );
add_action( 'wp_ajax_nopriv_ad_materials_calc_area_calculate', 'ad_materials_calc_ajax_area_calculate' );
add_action( 'wp_ajax_ad_materials_calc_email_area_estimate', 'ad_materials_calc_email_area_estimate' );
add_action( 'wp_ajax_nopriv_ad_materials_calc_email_area_estimate', 'ad_materials_calc_email_area_estimate' );

function ad_materials_calc_enqueue_area_assets() {

    wp_enqueue_style( 'ad-materials-calculator-areas' );
    wp_enqueue_script( 'ad-materials-calculator-areas' );

    wp_localize_script(
        'ad-materials-calculator-areas',
        'adMaterialsCalculatorAreas',
        [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ad_materials_calc_area_calculate' ),
            'i18n'    => [
                'calculating'          => __( 'Calculating your material kit...', 'ad-materials-calculator' ),
                'empty'                => __( 'Enter your project dimensions to calculate the materials.', 'ad-materials-calculator' ),
                'error'                => __( 'The estimate could not be calculated. Please check your selections and try again.', 'ad-materials-calculator' ),
                'sending'              => __( 'Sending your estimate...', 'ad-materials-calculator' ),
                'squareFeet'           => __( 'sq ft', 'ad-materials-calculator' ),
                'projectUpdating'      => __( 'Updating your project...', 'ad-materials-calculator' ),
                'projectIncomplete'    => __( 'Complete the selected surface dimensions', 'ad-materials-calculator' ),
                'incompleteDimensions' => __( 'Complete the dimensions for %s to update your project estimate.', 'ad-materials-calculator' ),
                'wallNumber'           => __( 'Wall %d', 'ad-materials-calculator' ),
                'roomNumber'           => __( 'Room %d', 'ad-materials-calculator' ),
                'ceiling'              => __( 'Ceiling', 'ad-materials-calculator' ),
                'removeExtra'          => __( 'Remove', 'ad-materials-calculator' ),
            ],
        ]
    );
}

function ad_materials_calc_ajax_area_calculate() {

    if ( empty( $_POST['nonce'] ) || ! is_string( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ad_materials_calc_area_calculate' ) ) {
        wp_send_json_error( ['message' => __( 'The calculator request could not be validated.', 'ad-materials-calculator' )], 403 );
    }

    $area    = isset( $_POST['area'] ) && is_string( $_POST['area'] ) ? sanitize_key( wp_unslash( $_POST['area'] ) ) : '';
    $payload = isset( $_POST['payload'] ) && is_array( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : [];

    $callback = 'ad_materials_calc_build_' . $area . '_result';

    if ( ! in_array( $area, ['bathroom_shower', 'countertop', 'wall_ceiling', 'facade', 'floor', 'balcony', 'pool', 'kitchen'], true ) || ! function_exists( $callback ) ) {
        wp_send_json_error( ['message' => __( 'This area calculator is not available yet.', 'ad-materials-calculator' )], 400 );
    }

    $result = $callback( $payload );

    if ( ! empty( $result['error'] ) ) {
        wp_send_json_error( ['message' => $result['error']], 400 );
    }

    // New cart nonces remain valid for at least half their configured lifetime
    $nonce_life          = apply_filters( 'nonce_life', DAY_IN_SECONDS, 'ad_materials_calc_add_group_to_cart' );
    $result['cache_ttl'] = is_numeric( $nonce_life ) && is_finite( (float) $nonce_life ) ? max( 0, min( 300, (int) floor( (float) $nonce_life / 2 ) - 30 ) ) : 0;

    wp_send_json_success( $result );
}

function ad_materials_calc_get_area_system_selection( array $payload, array $systems = [] ) {

    $systems = $systems ?: ['Terrazzo Granitech Medium', 'Terrazzo Granitech Fine', 'Terrazzo Granitech Microterrazzo'];

    if ( isset( $payload['system'] ) && ! in_array( $payload['system'], $systems, true ) ) {
        return ['error' => __( 'Select a finish available for this preset.', 'ad-materials-calculator' )];
    }

    $system  = ad_materials_calc_area_choice_value( $payload['system'] ?? '', $systems, $systems[0] );
    $key     = $system === 'Terrazzo Granitech Gaia' ? 'pigment_gaia' : ( in_array( $system, ['Terrazzo Granitech Fine', 'Terrazzo Granitech Micromosaic'], true ) ? 'pigment_fine' : 'pigment_medium' );
    $choices = array_column( ad_materials_calc_get_area_pigment_choices( $system ), 'value' );
    $pigment = ad_materials_calc_area_choice_value( $payload[$key] ?? '', $choices, 'Unpigmented' );
    $finish  = '';

    if ( isset( $payload[$key] ) && ! in_array( $payload[$key], $choices, true ) && $system !== 'Terrazzo Granitech Monolith' ) {
        return ['error' => __( 'The selected pigment is no longer available. Choose a current swatch.', 'ad-materials-calculator' )];
    }

    if ( $system === 'Terrazzo Granitech Microterrazzo' ) {
        $finishes = array_column( ad_materials_calc_get_area_microterrazzo_choices(), 'value' );
        $finish   = ad_materials_calc_area_choice_value( $payload['finish_color'] ?? '', $finishes, $finishes[0] ?? '' );

        if ( empty( $finishes ) || ( isset( $payload['finish_color'] ) && ! in_array( $payload['finish_color'], $finishes, true ) ) ) {
            return ['error' => __( 'Choose an available Microterrazzo blend.', 'ad-materials-calculator' )];
        }
    }

    $stone = '';

    if ( in_array( $system, ['Terrazzo Granitech Micromosaic', 'Terrazzo Granitech Monolith'], true ) ) {
        $stones = array_column( ad_materials_calc_get_area_stone_choices(), 'value' );

        if ( empty( $stones ) || ( isset( $payload['stone'] ) && ! in_array( $payload['stone'], $stones, true ) ) ) {
            return ['error' => __( 'The selected stone is no longer available. Choose a current swatch.', 'ad-materials-calculator' )];
        }

        $stone = ad_materials_calc_get_area_stone_selection( $payload );
    }

    return [
        'system'  => $system,
        'pigment' => $pigment,
        'finish'  => $finish,
        'stone'   => $stone,
    ];
}

function ad_materials_calc_render_area_finish_color_steps( array $options = [], $area = '' ) {

    $medium = ad_materials_calc_get_area_pigment_choices( 'Terrazzo Granitech Medium' );
    $fine   = ad_materials_calc_get_area_pigment_choices( 'Terrazzo Granitech Fine' );
    $micro  = ad_materials_calc_get_area_microterrazzo_choices();
    ?>
    <section class="ad-area-calc__step">
        <p class="ad-area-calc__step-heading"><span><?php esc_html_e( 'Step 2', 'ad-materials-calculator' ); ?></span><?php esc_html_e( 'Pick your finish', 'ad-materials-calculator' ); ?></p>
        <div class="ad-area-calc__cards" data-ad-area-buttons="system">
            <?php ad_materials_calc_area_choice_button( 'Terrazzo Granitech Medium', __( 'Medium', 'ad-materials-calculator' ), __( 'The coarsest grain - pronounced body and relief', 'ad-materials-calculator' ), true ); ?>
            <?php ad_materials_calc_area_choice_button( 'Terrazzo Granitech Fine', __( 'Fine', 'ad-materials-calculator' ), __( 'The finest grain - smooth, velvety body', 'ad-materials-calculator' ) ); ?>
            <?php ad_materials_calc_area_choice_button( 'Terrazzo Granitech Microterrazzo', __( 'Microterrazzo', 'ad-materials-calculator' ), __( 'Embedded beige quartz with touches of black - three blends', 'ad-materials-calculator' ) ); ?>
            <?php foreach ( $options as $option ): ?>
                <?php ad_materials_calc_area_choice_button( $option['value'], $option['label'], $option['description'] ); ?>
            <?php endforeach; ?>
        </div>
        <p class="ad-area-calc__note"><?php esc_html_e( 'Every finish takes any movement - calm and modern to boldly artistic. The trowel writes the pattern; the finish sets the grain.', 'ad-materials-calculator' ); ?></p>
        <div data-ad-area-microterrazzo hidden>
            <p class="ad-area-calc__subheading"><?php esc_html_e( 'Microterrazzo blend', 'ad-materials-calculator' ); ?></p>
            <?php echo ad_materials_calc_render_area_tiles( 'finish_color', $micro, 'compact', '600Q' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
            <p class="ad-area-calc__note"><?php esc_html_e( 'White base, tintable with any of our colors - lighter shades keep the quartz visible.', 'ad-materials-calculator' ); ?></p>
        </div>
    </section>
    <section class="ad-area-calc__step">
        <p class="ad-area-calc__step-heading"><span><?php esc_html_e( 'Step 3', 'ad-materials-calculator' ); ?></span><?php echo esc_html( array_intersect( ['Terrazzo Granitech Monolith', 'Terrazzo Granitech Micromosaic'], array_column( $options, 'value' ) ) ? __( 'Pick your color or stone', 'ad-materials-calculator' ) : __( 'Pick your color', 'ad-materials-calculator' ) ); ?></p>
        <?php if ( array_intersect( ['Terrazzo Granitech Monolith', 'Terrazzo Granitech Micromosaic'], array_column( $options, 'value' ) ) ): ?>
            <div data-ad-area-stones hidden>
                <p class="ad-area-calc__note ad-area-calc__note--intro" data-ad-monolith-note hidden><?php esc_html_e( 'Twenty natural blends - the color lives in the stone itself. Your last step: pick it, and the kit is complete.', 'ad-materials-calculator' ); ?></p>
                <p class="ad-area-calc__subheading"><?php esc_html_e( 'Pick your stone first', 'ad-materials-calculator' ); ?></p>
                <?php echo ad_materials_calc_render_area_tiles( 'stone', ad_materials_calc_get_area_stone_choices(), 'stone', 'Santorini' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
                <p class="ad-area-calc__note" data-ad-micromosaic-note hidden><?php echo esc_html( $area === 'kitchen' ? __( 'Micromosaic dresses the floor - then choose the color of the base below. For counters and walls, build a second kit in Medium or Fine.', 'ad-materials-calculator' ) : __( 'Then choose the color of the base below - the stone sets the field, the tint sets the tone.', 'ad-materials-calculator' ) ); ?></p>
                <p class="ad-area-calc__note" data-ad-monolith-note hidden><?php echo wp_kses_post( sprintf( __( 'See every blend at %s.', 'ad-materials-calculator' ), '<a href="' . esc_url( home_url( '/monolith/' ) ) . '">terrazzo-usa.com/monolith</a>' ) ); ?></p>
            </div>
        <?php endif; ?>
        <div data-ad-area-pigments="medium"><?php echo ad_materials_calc_render_area_tiles( 'pigment_medium', $medium, '', 'Wet Concrete' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></div>
        <p class="ad-area-calc__subheading" data-ad-micromosaic-color-heading hidden><?php esc_html_e( 'Pick your color next', 'ad-materials-calculator' ); ?></p>
        <div data-ad-area-pigments="fine" hidden><?php echo ad_materials_calc_render_area_tiles( 'pigment_fine', $fine, '', 'Wet Concrete' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></div>
        <?php if ( in_array( 'Terrazzo Granitech Gaia', array_column( $options, 'value' ), true ) ): ?>
            <div data-ad-area-pigments="gaia" hidden><?php echo ad_materials_calc_render_area_tiles( 'pigment_gaia', ad_materials_calc_get_area_pigment_choices( 'Terrazzo Granitech Gaia' ), '', 'Wet Concrete' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></div>
            <p class="ad-area-calc__note" data-ad-gaia-notice hidden><?php esc_html_e( 'Colorant usage: Use 1/15 of one pigment container for each Gaia Quart and 1/4 of one pigment container for each Gaia Gallon.', 'ad-materials-calculator' ); ?></p>
        <?php endif; ?>
        <p class="ad-area-calc__note" data-ad-palette-note><?php esc_html_e( 'The full standard palette - over 50 colors in warm, cool and earth tones.', 'ad-materials-calculator' ); ?></p>
    </section>
    <?php
}

function ad_materials_calc_render_area_summary( $title ) {
    ?>
    <aside class="ad-area-calc__summary">
        <h3><?php echo esc_html( $title ); ?></h3>
        <p class="ad-area-calc__project-description" data-ad-area-project-description><?php esc_html_e( 'Updating your project...', 'ad-materials-calculator' ); ?></p>
        <div class="ad-area-calc__breakdown" data-ad-area-breakdown></div>
        <p class="ad-area-calc__included"><?php esc_html_e( 'Included materials - updates with your room', 'ad-materials-calculator' ); ?></p>
        <div class="ad-area-calc__status" data-ad-area-status><?php esc_html_e( 'Calculating your material kit...', 'ad-materials-calculator' ); ?></div>
        <div data-ad-area-items></div>
        <div data-ad-area-notices></div>
        <div class="ad-area-calc__total"><span><?php esc_html_e( 'Estimated materials total', 'ad-materials-calculator' ); ?></span><strong data-ad-area-price><?php echo wp_kses_post( function_exists( 'wc_price' ) ? wc_price( 0 ) : '$0.00' ); ?></strong></div>
        <p class="ad-area-calc__rate" data-ad-area-rate></p>
        <div data-ad-area-cart></div>
        <p class="ad-area-calc__shipping"><?php esc_html_e( 'Materials only. Shipping and tax are calculated at checkout.', 'ad-materials-calculator' ); ?></p>
        <details class="ad-area-calc__email">
            <summary><?php esc_html_e( 'Not ready to order? Email this estimate', 'ad-materials-calculator' ); ?></summary>
            <form data-ad-email-form>
                <label><?php esc_html_e( 'Email address', 'ad-materials-calculator' ); ?><input type="email" name="email" autocomplete="email" required></label>
                <button type="submit" disabled><?php esc_html_e( 'Send estimate', 'ad-materials-calculator' ); ?></button>
                <p role="status" data-ad-email-status></p>
            </form>
        </details>
    </aside>
    <?php
}

function ad_materials_calc_area_number_field( $key, $label, $value = '', $unit = 'ft' ) {

    $units = ['ft' => __( 'ft', 'ad-materials-calculator' ), 'in' => __( 'in', 'ad-materials-calculator' ), 'sq ft' => __( 'sq ft', 'ad-materials-calculator' )];
    $unit  = $units[$unit] ?? $unit;
    ?>
    <label class="ad-area-calc__field"><span><?php echo esc_html( $label ); ?></span><span><input type="number" min="0" step="0.5" inputmode="decimal" value="<?php echo esc_attr( $value ); ?>" data-ad-area-input="<?php echo esc_attr( $key ); ?>"><i><?php echo esc_html( $unit ); ?></i></span></label>
    <?php
}

function ad_materials_calc_area_choice_button( $value, $label, $description, $active = false ) {
    ?>
    <button type="button" class="ad-area-calc__card<?php echo $active ? ' is-active' : ''; ?>" data-ad-area-value="<?php echo esc_attr( $value ); ?>"><strong><?php echo esc_html( $label ); ?></strong><span><?php echo esc_html( $description ); ?></span></button>
    <?php
}

function ad_materials_calc_get_area_sheen_label( $sheen ) {

    $labels = [
        'Matte'      => __( 'Matte', 'ad-materials-calculator' ),
        'Satin'      => __( 'Satin', 'ad-materials-calculator' ),
        'Semi-Gloss' => __( 'Semi-Gloss', 'ad-materials-calculator' ),
    ];

    return $labels[$sheen] ?? $sheen;
}

function ad_materials_calc_area_positive_float( $value ) {

    if ( ! is_scalar( $value ) ) {
        return 0.0;
    }

    $value = trim( str_replace( ',', '.', (string) $value ) );

    return $value !== '' && is_numeric( $value ) && is_finite( (float) $value ) ? max( 0.0, (float) $value ) : 0.0;
}

function ad_materials_calc_area_choice_value( $value, array $allowed, $default ) {

    $value = is_scalar( $value ) ? trim( sanitize_text_field( (string) $value ) ) : '';

    return in_array( $value, $allowed, true ) ? $value : $default;
}

function ad_materials_calc_validate_area_dimension_groups( array $groups ) {

    foreach ( $groups as $label => $values ) {
        foreach ( $values as $value ) {
            if ( ad_materials_calc_area_positive_float( $value ) <= 0 ) {
                return sprintf( __( 'Enter all positive dimensions for %s before calculating this kit.', 'ad-materials-calculator' ), $label );
            }
        }
    }

    return '';
}

function ad_materials_calc_empty_area_result() {

    return [
        'area_sqft'        => 0,
        'area_sqm'         => 0,
        'items_html'       => '',
        'total_html'       => function_exists( 'wc_price' ) ? wc_price( 0 ) : '$0.00',
        'add_to_cart_html' => '',
    ];
}

function ad_materials_calc_get_area_pigment_choices( $system ) {

    $product_id = $system === 'Terrazzo Granitech Gaia' ? 14769 : ( in_array( $system, ['Terrazzo Granitech Fine', 'Terrazzo Granitech Micromosaic'], true ) ? 9932 : 9838 );
    $choices    = [
        [
            'label' => __( 'Unpigmented', 'ad-materials-calculator' ),
            'value' => 'Unpigmented',
            'image' => ad_wpforms_unpigmented_choice()['image'],
        ],
    ];

    if ( ! function_exists( 'wc_get_product' ) ) {
        return $choices;
    }

    $product = wc_get_product( $product_id );

    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return $choices;
    }

    foreach ( ad_wpforms_build_image_choices_from_product( $product ) as $choice ) {
        $choices[] = [
            'label' => $choice['label'],
            'value' => $choice['label'],
            'image' => $choice['image'],
        ];
    }

    return $choices;
}

function ad_materials_calc_get_area_microterrazzo_choices() {

    return function_exists( 'ad_wpforms_microterrazzo_finish_choices' ) ? ad_wpforms_microterrazzo_finish_choices() : [];
}

function ad_materials_calc_get_area_stone_choices() {

    return function_exists( 'ad_wpforms_monolith_stone_choices' ) ? ad_wpforms_monolith_stone_choices() : [];
}

function ad_materials_calc_get_area_stone_selection( array $payload ) {

    $choices = array_column( ad_materials_calc_get_area_stone_choices(), 'value' );

    return ad_materials_calc_area_choice_value( $payload['stone'] ?? '', $choices, $choices[0] ?? '' );
}

function ad_materials_calc_render_area_tiles( $key, array $choices, $modifier = '', $default = '' ) {

    $html               = '<div class="ad-area-calc__tiles' . ( $modifier ? ' ad-area-calc__tiles--' . esc_attr( $modifier ) : '' ) . '" role="radiogroup">';
    $index              = 0;
    $name               = wp_unique_id( $key . '_' );
    $default_exists     = in_array( $default, array_column( $choices, 'value' ), true );
    $blend_descriptions = $modifier === 'compact' ? [
        'Roma'   => __( 'The quietest scatter', 'ad-materials-calculator' ),
        '600Q'   => __( 'A balanced field', 'ad-materials-calculator' ),
        'Zambia' => __( 'The densest mix', 'ad-materials-calculator' ),
    ] : [];

    foreach ( $choices as $choice ) {
        $value = isset( $choice['value'] ) ? (string) $choice['value'] : '';
        $label = isset( $choice['label'] ) ? (string) $choice['label'] : $value;
        $image = isset( $choice['image'] ) ? (string) $choice['image'] : '';

        if ( $value === '' ) {
            continue;
        }

        $html .= '<label class="ad-area-calc__tile">';
        $is_checked = $default_exists ? $value === $default : $index === 0;
        $html .= '<input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" data-ad-area-input="' . esc_attr( $key ) . '"' . ( $is_checked ? ' checked' : '' ) . '>';
        $html .= '<span class="ad-area-calc__swatch"' . ( $image ? ' style="background-image:url(' . esc_url( $image ) . ')"' : '' ) . '></span>';
        $html .= '<span>' . esc_html( $label ) . '</span>';

        if ( isset( $blend_descriptions[$value] ) ) {
            $html .= '<small>' . esc_html( $blend_descriptions[$value] ) . '</small>';
        }

        $html .= '</label>';
        $index++;
    }

    return $html . '</div>';
}

function ad_materials_calc_merge_area_items( array $calculations ) {

    $items = [];

    foreach ( $calculations as $surface ) {
        foreach ( (array) ( $surface['calc']['items'] ?? [] ) as $item ) {
            $sku = (string) ( $item['sku'] ?? '' );

            if ( $sku === '' ) {
                continue;
            }

            if ( ! isset( $items[$sku] ) ) {
                $items[$sku] = $item;
                continue;
            }

            $items[$sku]['qty']     = (int) $items[$sku]['qty'] + (int) $item['qty'];
            $items[$sku]['partial'] = (float) $items[$sku]['partial'] + (float) $item['partial'];
        }
    }

    return array_values( $items );
}

function ad_materials_calc_build_area_result( $area_label, $system_label, array $calculations, array $selection_meta, array $breakdown_rows = [] ) {

    $items = ad_materials_calc_merge_area_items( $calculations );

    if ( empty( $items ) ) {
        return ['error' => __( 'No materials were calculated for these selections.', 'ad-materials-calculator' )];
    }

    $area_total = array_sum( array_column( $calculations, 'area' ) );
    $allowance  = (int) ceil( $area_total * 0.1 );
    $subtotal   = ad_materials_calc_get_items_subtotal( $items );
    $display    = ad_materials_calc_get_items_display_subtotal( $items );
    $meta       = [];
    $notices    = '';

    $breakdown = '<div class="ad-area-calc__breakdown">';

    foreach ( $calculations as $surface ) {
        $meta[] = sprintf( __( '%1$s: %2$s sq.ft.', 'ad-materials-calculator' ), $surface['label'], round( $surface['area'], 1 ) );

        if ( ! empty( $surface['calc']['notice'] ) ) {
            $notices .= '<div class="ad-area-calc__note">' . wp_kses_post( $surface['calc']['notice'] ) . '</div>';
            $meta[] = wp_strip_all_tags( $surface['calc']['notice'] );
        }
    }

    foreach ( $breakdown_rows ?: $calculations as $surface ) {
        $breakdown .= '<p>' . esc_html( $surface['label'] ) . '<strong>' . esc_html( sprintf( __( '%s sq ft', 'ad-materials-calculator' ), round( $surface['area'], 1 ) ) ) . '</strong></p>';
    }

    $breakdown .= '<p>' . esc_html__( 'Measured area', 'ad-materials-calculator' ) . '<strong>' . esc_html( sprintf( __( '%s sq ft', 'ad-materials-calculator' ), round( $area_total, 1 ) ) ) . '</strong></p>';
    $breakdown .= '<p>' . esc_html__( 'Material allowance', 'ad-materials-calculator' ) . '<strong>' . esc_html( sprintf( __( '10%% (%s sq ft)', 'ad-materials-calculator' ), $allowance ) ) . '</strong></p>';
    $breakdown .= '<p>' . esc_html__( 'Sizing area before package rounding', 'ad-materials-calculator' ) . '<strong>' . esc_html( sprintf( __( '%s sq ft', 'ad-materials-calculator' ), round( $area_total + $allowance, 1 ) ) ) . '</strong></p></div>';

    $meta                 = array_merge( $meta, $selection_meta );
    $cart                 = ad_materials_calc_build_area_cart_payload( $area_label, $system_label, $items, $area_total, $subtotal, $meta );
    $cart['pricing_tier'] = function_exists( 'ad_materials_calc_get_pricing_tier_from_answers' ) ? ad_materials_calc_get_pricing_tier_from_answers( ['total_area_sqft' => $area_total] ) : 'normal';

    return [
        'area_sqft'        => round( $area_total, 1 ),
        'area_sqm'         => round( $area_total / 10.7639, 1 ),
        'items_html'       => ad_materials_calc_render_area_items( $items ),
        'breakdown_html'   => $breakdown,
        'notices_html'     => $notices,
        'total_html'       => function_exists( 'wc_price' ) ? wc_price( $display ) : '$' . number_format( $display, 2 ),
        'rate_html'        => $area_total > 0 ? esc_html( sprintf( __( 'Complete material system - %s per measured sq ft', 'ad-materials-calculator' ), html_entity_decode( wp_strip_all_tags( wc_price( $display / $area_total ) ), ENT_QUOTES, 'UTF-8' ) ) ) : '',
        'add_to_cart_html' => ad_materials_calc_render_area_cart_form( $cart, $display ),
    ];
}

function ad_materials_calc_email_area_estimate() {

    if ( ! check_ajax_referer( 'ad_materials_calc_area_calculate', 'nonce', false ) ) {
        wp_send_json_error( ['message' => __( 'Please reload the calculator and try again.', 'ad-materials-calculator' )], 403 );
    }

    $email     = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $encoded   = isset( $_POST['payload'] ) && is_string( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
    $signature = isset( $_POST['signature'] ) && is_string( $_POST['signature'] ) ? wp_unslash( $_POST['signature'] ) : '';

    if ( ! is_email( $email ) || ! ad_materials_calc_is_valid_cart_payload_signature( $encoded, $signature ) ) {
        wp_send_json_error( ['message' => __( 'Enter a valid email address and calculate a current estimate first.', 'ad-materials-calculator' )], 400 );
    }

    $payload = ad_materials_calc_decode_cart_payload( $encoded );

    if ( ! is_array( $payload ) || empty( $payload['items'] ) ) {
        wp_send_json_error( ['message' => __( 'There are no materials to email.', 'ad-materials-calculator' )], 400 );
    }

    $ip_key    = 'ad_area_mail_ip_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
    $email_key = 'ad_area_mail_to_' . md5( strtolower( $email ) );

    if ( get_transient( $ip_key ) || get_transient( $email_key ) ) {
        wp_send_json_error( ['message' => __( 'Please wait ten minutes before sending another estimate.', 'ad-materials-calculator' )], 429 );
    }

    $body = wp_strip_all_tags( $payload['project_title'] ?? __( 'Materials estimate', 'ad-materials-calculator' ) ) . "\n\n";
    $body .= wp_strip_all_tags( str_replace( '<br>', "\n", $payload['project_meta'] ?? '' ) ) . "\n\n";
    $total = 0.0;

    foreach ( $payload['items'] as $item ) {
        $product = ad_materials_calc_get_product_by_sku( $item['sku'] ?? '' );

        if ( ! $product ) {
            wp_send_json_error( ['message' => __( 'A material is no longer available. Please calculate again.', 'ad-materials-calculator' )], 400 );
        }

        $subtotal = ad_materials_calc_get_display_unit_price_for_item( $item ) * (int) $item['qty'];
        $total += $subtotal;
        $body .= sprintf( __( '%1$d x %2$s - %3$s', 'ad-materials-calculator' ), (int) $item['qty'], wp_strip_all_tags( $product->get_name() ), html_entity_decode( wp_strip_all_tags( wc_price( $subtotal ) ), ENT_QUOTES, 'UTF-8' ) ) . "\n";
    }

    $body .= "\n" . __( 'Estimated materials total:', 'ad-materials-calculator' ) . ' ' . html_entity_decode( wp_strip_all_tags( wc_price( $total ) ), ENT_QUOTES, 'UTF-8' );
    $body .= "\n" . __( 'Shipping and tax are calculated at checkout. Prices and availability may change.', 'ad-materials-calculator' );

    // Reserve the cooldown during sending, but release it if delivery fails
    set_transient( $ip_key, 1, 10 * MINUTE_IN_SECONDS );
    set_transient( $email_key, 1, 10 * MINUTE_IN_SECONDS );
    $sent = wp_mail( $email, __( 'Your TERRAZZO GraniTech materials estimate', 'ad-materials-calculator' ), $body );

    if ( ! $sent ) {
        delete_transient( $ip_key );
        delete_transient( $email_key );

        wp_send_json_error( ['message' => __( 'The estimate could not be emailed. Please try again later.', 'ad-materials-calculator' )], 500 );
    }

    wp_send_json_success( ['message' => __( 'Your estimate has been sent.', 'ad-materials-calculator' )] );
}

function ad_materials_calc_build_area_cart_payload( $area_label, $system_label, array $items, $area, $subtotal, array $meta ) {

    $payload_items = [];

    foreach ( $items as $item ) {
        $payload_items[] = [
            'sku'        => (string) ( $item['sku'] ?? '' ),
            'qty'        => (int) ( $item['qty'] ?? 0 ),
            'unit_price' => (float) ( $item['unit_price'] ?? 0 ),
        ];
    }

    return [
        'surface_type'             => $area_label,
        'system_label'             => $system_label,
        'project_title'            => $area_label,
        'project_meta'             => implode( '<br>', array_map( 'esc_html', $meta ) ),
        'area_sqft'                => (float) $area,
        'discount_type'            => 'none',
        'discount_label'           => __( 'No discount', 'ad-materials-calculator' ),
        'discount_requested_value' => 0,
        'discount_applied_value'   => 0,
        'subtotal_before_discount' => (float) $subtotal,
        'final_total'              => (float) $subtotal,
        'pricing_tier'             => 'normal',
        'items'                    => $payload_items,
    ];
}

function ad_materials_calc_render_area_items( array $items ) {

    $html = '';

    foreach ( $items as $item ) {
        $qty     = (int) ( $item['qty'] ?? 0 );
        $unit    = ad_materials_calc_get_display_unit_price_for_item( $item );
        $partial = $unit * $qty;
        $html .= '<div class="ad-area-calc__item"><div><strong>' . esc_html( $item['name'] ?? '' ) . '</strong><span>' . esc_html( sprintf( _n( '%d unit', '%d units', $qty, 'ad-materials-calculator' ), $qty ) ) . '</span></div><b>' . wp_kses_post( wc_price( $partial ) ) . '</b></div>';
    }

    return $html;
}

function ad_materials_calc_render_area_cart_form( array $payload, $total ) {

    $encoded   = ad_materials_calc_encode_cart_payload( $payload );
    $signature = $encoded ? ad_materials_calc_sign_cart_payload( $encoded ) : '';

    if ( ! $encoded || ! $signature ) {
        return '';
    }

    $html = '<form method="post" class="ad-area-calc__cart-form">';
    $html .= wp_nonce_field( 'ad_materials_calc_add_group_to_cart', 'ad_materials_calc_add_group_nonce', true, false );
    $html .= '<input type="hidden" name="ad_materials_calc_action" value="add_group_to_cart">';
    $html .= '<input type="hidden" name="ad_materials_calc_payload" value="' . esc_attr( $encoded ) . '">';
    $html .= '<input type="hidden" name="ad_materials_calc_payload_signature" value="' . esc_attr( $signature ) . '">';
    $html .= '<button type="submit" class="button alt">' . esc_html( sprintf( __( 'Add complete kit - %s', 'ad-materials-calculator' ), wp_strip_all_tags( wc_price( $total ) ) ) ) . '</button>';

    return $html . '</form>';
}