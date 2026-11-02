<?php
/** Area preset registration, rendering and calculation adapters. Recipes remain in Cases 1-13 */

function ad_materials_calc_register_area_preset( $area, callable $config ) {

    $GLOBALS['ad_materials_calc_area_presets'][$area] = $config;

    add_shortcode( 'ad_materials_calculator_' . $area, function () use ( $area ) {
        return ad_materials_calc_render_area_preset( $area );
    } );
}

function ad_materials_calc_get_area_preset( $area ) {

    // Resolve translated labels at use time, after the plugin text domain is loaded
    $config = $GLOBALS['ad_materials_calc_area_presets'][$area] ?? null;

    return $config ? $config() : [];
}

function ad_materials_calc_preset_field( $key, $label, $value = '', $unit = 'ft' ) {

    return ['key' => $key, 'label' => $label, 'value' => $value, 'unit' => $unit];
}

function ad_materials_calc_preset_known_fields( array $config ) {

    return $config['known_fields'] ?? [
        'total' => ['key' => 'known_area', 'label' => $config['known_label'] ?? __( 'Measured area', 'ad-materials-calculator' ), 'route' => $config['route']],
    ];
}

function ad_materials_calc_render_preset_surface( $key, array $surface ) {
    ?>
    <div class="ad-area-calc__surface<?php echo ! empty( $surface['checked'] ) ? ' is-active' : ''; ?>" data-ad-preset-surface="<?php echo esc_attr( $key ); ?>"<?php echo isset( $surface['location'] ) ? ' data-ad-location="' . esc_attr( $surface['location'] ) . '"' : ''; ?>>
        <div class="ad-area-calc__surface-head">
            <label><input type="checkbox" data-ad-area-input="<?php echo esc_attr( $key ); ?>_enabled"<?php checked( ! empty( $surface['checked'] ) ); ?>> <?php echo esc_html( $surface['label'] ); ?></label>
            <strong data-ad-area-preview="<?php echo esc_attr( $key ); ?>">0 <?php esc_html_e( 'sq ft', 'ad-materials-calculator' ); ?></strong>
        </div>
        <div class="ad-area-calc__optional-fields"<?php echo empty( $surface['checked'] ) ? ' hidden' : ''; ?>>
            <?php if ( ! empty( $surface['fields'] ) ): ?>
            <div class="ad-area-calc__fields ad-area-calc__fields--<?php echo count( $surface['fields'] ) === 3 ? 'three' : 'two'; ?>">
                <?php foreach ( $surface['fields'] as $field ): ?>
                    <?php ad_materials_calc_area_number_field( $key . '_' . $field['key'], $field['label'], $field['value'], $field['unit'] ); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ( ! empty( $surface['ceiling'] ) ): ?>
                <label class="ad-area-calc__check"><input type="checkbox" data-ad-area-input="<?php echo esc_attr( $key ); ?>_ceiling" checked> <?php esc_html_e( 'Include the ceiling', 'ad-materials-calculator' ); ?></label>
                <?php if ( ! empty( $surface['ceiling_note'] ) ): ?>
                    <p class="ad-area-calc__note"><?php echo esc_html( $surface['ceiling_note'] ); ?></p>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ( ! empty( $surface['extra'] ) ): ?>
                <div data-ad-area-extra="<?php echo esc_attr( $key ); ?>" hidden>
                    <div class="ad-area-calc__fields ad-area-calc__fields--two">
                        <?php foreach ( $surface['extra']['fields'] as $field ): ?>
                            <?php ad_materials_calc_area_number_field( $key . '_extra_' . $field['key'], $field['label'], $field['value'], $field['unit'] ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" class="ad-area-calc__add-more" data-ad-area-extra-toggle="<?php echo esc_attr( $key ); ?>" data-ad-extra-add-label="<?php echo esc_attr( $surface['extra']['label'] ); ?>" aria-expanded="false"><?php echo esc_html( $surface['extra']['label'] ); ?></button>
            <?php endif; ?>
        </div>
        <?php if ( ! empty( $surface['note'] ) ): ?>
            <p class="ad-area-calc__note" data-ad-surface-note><?php echo esc_html( $surface['note'] ); ?></p>
        <?php endif; ?>
        <?php if ( ! empty( $surface['overlap_note'] ) ): ?>
            <p class="ad-area-calc__note" data-ad-overlap-note hidden><?php echo esc_html( $surface['overlap_note'] ); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

function ad_materials_calc_render_preset_repeater( $type, $label, array $fields, $count, $limit ) {

    $row_label = $type === 'walls' ? __( 'Wall', 'ad-materials-calculator' ) : __( 'Room', 'ad-materials-calculator' );
    ?>
    <div data-ad-area-repeater="<?php echo esc_attr( $type ); ?>" data-ad-limit="<?php echo esc_attr( $limit ); ?>">
        <div data-ad-area-rows>
            <?php for ( $i = 0; $i < $count; $i++ ): ?>
                <div class="ad-area-calc__surface is-active" data-ad-area-row>
                    <label><input type="checkbox" data-ad-row-input="enabled" checked> <?php echo esc_html( $row_label ); ?> <span data-ad-row-number><?php echo esc_html( $i + 1 ); ?></span></label>
                    <div class="ad-area-calc__fields ad-area-calc__fields--three">
                        <?php foreach ( $fields as $field ): ?>
                            <label class="ad-area-calc__field"><span><?php echo esc_html( $field['label'] ); ?></span><span><input type="number" min="0" step="0.5" value="<?php echo esc_attr( $field['value'] ); ?>" data-ad-row-input="<?php echo esc_attr( $field['key'] ); ?>"><i><?php esc_html_e( 'ft', 'ad-materials-calculator' ); ?></i></span></label>
                        <?php endforeach; ?>
                    </div>
                    <?php if ( $type === 'rooms' ): ?>
                        <label><input type="checkbox" data-ad-row-input="ceiling" checked> <?php esc_html_e( 'Include this room\'s entire ceiling', 'ad-materials-calculator' ); ?></label>
                    <?php endif; ?>
                    <button type="button" class="ad-area-calc__add-more" data-ad-remove-row><?php esc_html_e( 'Remove', 'ad-materials-calculator' ); ?></button>
                </div>
            <?php endfor; ?>
        </div>
        <button type="button" class="ad-area-calc__add-more" data-ad-add-row><?php echo esc_html( sprintf( __( '+ Add another %s', 'ad-materials-calculator' ), $label ) ); ?></button>
    </div>
    <?php
}

function ad_materials_calc_render_area_preset( $area ) {

    $config = ad_materials_calc_get_area_preset( $area );

    if ( ! function_exists( 'wc_get_product' ) ) {
        return '<p>' . esc_html__( 'The materials calculator requires WooCommerce.', 'ad-materials-calculator' ) . '</p>';
    }

    ad_materials_calc_enqueue_area_assets();
    ob_start();
    ?>
    <div class="ad-area-calc" data-ad-area-calc="<?php echo esc_attr( $area ); ?>" data-ad-preset="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
        <header class="ad-area-calc__intro">
            <h2><?php echo esc_html( $config['heading'] ); ?></h2>
            <p><?php esc_html_e( 'Adjust the measurements - your kit and price update instantly.', 'ad-materials-calculator' ); ?></p>
        </header>
        <div class="ad-area-calc__main">
            <section class="ad-area-calc__step">
                <p class="ad-area-calc__step-heading"><span><?php esc_html_e( 'Step 1', 'ad-materials-calculator' ); ?></span><?php esc_html_e( 'Enter your dimensions', 'ad-materials-calculator' ); ?></p>
                <?php if ( ! empty( $config['locations'] ) ): ?>
                    <div class="ad-area-calc__cards ad-area-calc__cards--two" data-ad-area-buttons="location">
                        <?php foreach ( $config['locations'] as $value => $label ): ?>
                            <?php ad_materials_calc_area_choice_button( $value, $label, $config['location_copy'][$value] ?? '', $value === array_key_first( $config['locations'] ) ); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="ad-area-calc__mode">
                    <button type="button" class="is-active" data-ad-area-mode="dimensions" aria-pressed="true"><?php echo esc_html( $config['dimension_mode_label'] ); ?><span class="ad-area-calc__recommended"><?php esc_html_e( 'Recommended', 'ad-materials-calculator' ); ?></span></button>
                    <button type="button" data-ad-area-mode="known" aria-pressed="false"><?php echo esc_html( $config['known_mode_label'] ); ?></button>
                </div>
                <div data-ad-area-mode-panel="dimensions">
                    <?php if ( ! empty( $config['walls_layout'] ) ): ?>
                        <div class="ad-area-calc__cards ad-area-calc__cards--two" data-ad-area-buttons="layout">
                            <?php ad_materials_calc_area_choice_button( 'standard', $area === 'facade' ? __( 'Whole building', 'ad-materials-calculator' ) : __( 'Standard room', 'ad-materials-calculator' ), $config['layout_copy']['standard'] ?? __( 'All four walls from length, width and height', 'ad-materials-calculator' ), true ); ?>
                            <?php ad_materials_calc_area_choice_button( 'individual', __( 'Individual walls', 'ad-materials-calculator' ), $config['layout_copy']['individual'] ?? __( 'Only the walls you are finishing', 'ad-materials-calculator' ) ); ?>
                        </div>
                    <?php endif; ?>
                    <div data-ad-layout="standard">
                        <?php if ( ! empty( $config['dimensions_note'] ) ): ?>
                            <p class="ad-area-calc__note ad-area-calc__note--intro"><?php echo esc_html( $config['dimensions_note'] ); ?></p>
                        <?php endif; ?>
                        <?php foreach ( $config['dimensions_notes'] ?? [] as $location => $note ): ?>
                            <p class="ad-area-calc__note ad-area-calc__note--intro" data-ad-location="<?php echo esc_attr( $location ); ?>"><?php echo esc_html( $note ); ?></p>
                        <?php endforeach; ?>
                        <?php foreach ( $config['surfaces'] as $key => $surface ): ?>
                            <?php if ( ! empty( $surface['ceiling'] ) && ! empty( $config['ceiling_note'] ) ): ?>
                                <?php $surface['ceiling_note'] = $config['ceiling_note']; ?>
                            <?php endif; ?>
                            <?php ad_materials_calc_render_preset_surface( $key, $surface ); ?>
                        <?php endforeach; ?>
                    </div>
                    <?php if ( ! empty( $config['walls_layout'] ) ): ?>
                        <div data-ad-layout="individual" hidden>
                            <?php if ( ! empty( $config['individual_note'] ) ): ?>
                                <p class="ad-area-calc__note ad-area-calc__note--intro"><?php echo esc_html( $config['individual_note'] ); ?></p>
                            <?php endif; ?>
                            <?php ad_materials_calc_render_preset_repeater( 'walls', __( 'wall', 'ad-materials-calculator' ), [ad_materials_calc_preset_field( 'width', __( 'Width', 'ad-materials-calculator' ), 12 ), ad_materials_calc_preset_field( 'height', __( 'Height', 'ad-materials-calculator' ), $area === 'facade' ? 10 : 9 )], 4, 8 ); ?>
                            <?php if ( $area === 'wall_ceiling' ): ?>
                                <?php ad_materials_calc_render_preset_surface( 'individual_ceiling', ['label' => __( 'Ceiling', 'ad-materials-calculator' ), 'checked' => false, 'fields' => [ad_materials_calc_preset_field( 'length', __( 'Length', 'ad-materials-calculator' ) ), ad_materials_calc_preset_field( 'width', __( 'Width', 'ad-materials-calculator' ) )]] ); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $config['measurement_note'] ) ): ?>
                            <p class="ad-area-calc__note"><?php echo esc_html( $config['measurement_note'] ); ?></p>
                        <?php endif; ?>
                        <?php if ( $area === 'wall_ceiling' ): ?>
                            <div data-ad-additional-rooms>
                                <p class="ad-area-calc__note"><?php echo esc_html( $config['rooms_note'] ); ?></p>
                                <?php ad_materials_calc_render_preset_repeater( 'rooms', __( 'room', 'ad-materials-calculator' ), [ad_materials_calc_preset_field( 'length', __( 'Room length', 'ad-materials-calculator' ), 12 ), ad_materials_calc_preset_field( 'width', __( 'Room width', 'ad-materials-calculator' ), 10 ), ad_materials_calc_preset_field( 'height', __( 'Wall height', 'ad-materials-calculator' ), 9 )], 1, 5 ); ?>
                                <p class="ad-area-calc__note" data-ad-multiroom-note hidden><?php echo esc_html( $config['multiroom_note'] ); ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div data-ad-area-mode-panel="known" hidden>
                    <?php if ( ! empty( $config['known_note'] ) ): ?>
                        <p class="ad-area-calc__note"><?php echo esc_html( $config['known_note'] ); ?></p>
                    <?php endif; ?>
                    <div class="ad-area-calc__fields ad-area-calc__fields--two">
                        <?php foreach ( ad_materials_calc_preset_known_fields( $config ) as $field ): ?>
                            <?php ad_materials_calc_area_number_field( $field['key'], $field['label'], '', 'sq ft' ); ?>
                        <?php endforeach; ?>
                    </div>
                    <?php if ( ! empty( $config['known_area_note'] ) ): ?>
                        <p class="ad-area-calc__note"><?php echo esc_html( $config['known_area_note'] ); ?></p>
                    <?php endif; ?>
                </div>
            </section>
            <?php ad_materials_calc_render_area_finish_color_steps( $config['finishes'] ?? [], $area ); ?>
            <?php if ( $config['steps'] === 5 ): ?>
                <section class="ad-area-calc__step" data-ad-sheen-step>
                    <p class="ad-area-calc__step-heading"><span><?php esc_html_e( 'Step 4', 'ad-materials-calculator' ); ?></span><?php esc_html_e( 'Pick your sheen', 'ad-materials-calculator' ); ?></p>
                    <div class="ad-area-calc__cards" data-ad-area-buttons="sheen">
                        <?php foreach ( $config['sheens'] as $sheen ): ?>
                            <?php ad_materials_calc_area_choice_button( $sheen, ad_materials_calc_get_area_sheen_label( $sheen ), $config['sheen_copy'][$sheen] ?? '', $sheen === $config['default_sheen'] ); ?>
                        <?php endforeach; ?>
                    </div>
                    <?php if ( ! empty( $config['sheen_note'] ) ): ?>
                        <p class="ad-area-calc__note" data-ad-sheen-note><?php echo esc_html( $config['sheen_note'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $config['exterior_note'] ) ): ?>
                        <p class="ad-area-calc__note" data-ad-exterior-note hidden><?php echo esc_html( $config['exterior_note'] ); ?></p>
                    <?php endif; ?>
                    <p class="ad-area-calc__note" data-ad-fixed-sheen hidden><?php esc_html_e( 'Exterior and Micromosaic presets use a fixed matte finish. Monolith retains its natural stone finish without a sealer.', 'ad-materials-calculator' ); ?></p>
                </section>
            <?php endif; ?>
            <section class="ad-area-calc__step" data-ad-protection-step>
                <p class="ad-area-calc__step-heading"><span><?php echo esc_html( sprintf( __( 'Step %d', 'ad-materials-calculator' ), $config['steps'] ) ); ?></span><?php esc_html_e( 'Pick your protection level', 'ad-materials-calculator' ); ?></p>
                <div class="ad-area-calc__cards ad-area-calc__cards--two" data-ad-area-buttons="protection">
                    <?php ad_materials_calc_area_choice_button( 'Standard Duty', __( 'Standard', 'ad-materials-calculator' ), $config['standard_copy'], true ); ?>
                    <?php ad_materials_calc_area_choice_button( 'Max Duty', __( 'Maximum', 'ad-materials-calculator' ), $config['maximum_copy'] ); ?>
                </div>
                <?php if ( ! empty( $config['protection_note'] ) ): ?>
                    <p class="ad-area-calc__note" data-ad-protection-note><?php echo esc_html( $config['protection_note'] ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $config['micromosaic_protection_note'] ) ): ?>
                    <p class="ad-area-calc__note" data-ad-micromosaic-protection-note hidden><?php echo esc_html( $config['micromosaic_protection_note'] ); ?></p>
                <?php endif; ?>
                <?php if ( $area === 'pool' ): ?>
                    <p class="ad-area-calc__note" data-ad-pool-deck-note><?php esc_html_e( 'Every deck finishes matte, sealed and topped with the certified Anti-Slip R-11 coat - sure footing barefoot, wet or dry.', 'ad-materials-calculator' ); ?></p>
                <?php endif; ?>
                <p class="ad-area-calc__note" data-ad-fixed-protection hidden><?php esc_html_e( 'The selected preset uses a fixed protection build.', 'ad-materials-calculator' ); ?></p>
                <?php if ( ! empty( $config['mesh_option'] ) ): ?>
                    <label class="ad-area-calc__check"><input type="checkbox" data-ad-area-input="mesh_enabled" checked> <?php echo esc_html( $config['mesh_copy'] ?? __( 'Include fiberglass mesh reinforcement - recommended over fresh stucco and at transitions', 'ad-materials-calculator' ) ); ?></label>
                <?php endif; ?>
            </section>
        </div>
        <?php ad_materials_calc_render_area_summary( $config['title'] ); ?>
    </div>
    <?php

    return ob_get_clean();
}

function ad_materials_calc_preset_term_area( array $surface, $key, array $payload ) {

    $key = $surface['field_source'] ?? $key;

    if ( ! empty( $surface['area_callback'] ) ) {
        return call_user_func( $surface['area_callback'], $key, $payload );
    }

    $total = 0.0;

    if ( ! empty( $surface['parts'] ) ) {
        foreach ( $surface['parts'] as $part ) {
            $total += ad_materials_calc_preset_term_area( $part, $key, $payload );
        }

        return $total;
    }

    foreach ( $surface['terms'] as $term ) {
        $value = (float) $term[0];

        foreach ( array_slice( $term, 1 ) as $dimension ) {
            $value *= ad_materials_calc_area_positive_float( $payload[$key . '_' . $dimension] ?? 0 );
        }

        $total += $value;
    }

    return $total;
}

function ad_materials_calc_get_preset_areas( $area, array $payload ) {

    $config   = ad_materials_calc_get_area_preset( $area );
    $location = ad_materials_calc_preset_location( $config, $payload );
    $known    = ( $payload['mode'] ?? '' ) === 'known';
    $areas    = [];

    if ( $known ) {
        foreach ( ad_materials_calc_preset_known_fields( $config ) as $key => $field ) {
            $areas[$key] = ad_materials_calc_area_positive_float( $payload[$field['key']] ?? 0 );
        }

        if ( $area === 'kitchen' && ( $payload['system'] ?? '' ) === 'Terrazzo Granitech Micromosaic' ) {
            return ['floor' => $areas['floor'] ?? 0.0];
        }

        return $areas;
    }

    foreach ( $config['surfaces'] as $key => $surface ) {
        $areas[$key] = 0.0;

        if ( isset( $surface['location'] ) && $surface['location'] !== $location ) {
            continue;
        }

        if ( empty( $payload[$key . '_enabled'] ) ) {
            continue;
        }

        $areas[$key] = ad_materials_calc_preset_term_area( $surface, $key, $payload );

        if ( ! empty( $surface['parts'] ) ) {
            unset( $areas[$key] );

            foreach ( $surface['parts'] as $part_key => $part ) {
                $areas[$part_key] = ad_materials_calc_preset_term_area( $part, $key, $payload );
            }
        }

        if ( ! empty( $surface['ceiling'] ) && ! empty( $payload[$key . '_ceiling'] ) ) {
            $areas[$key] += ad_materials_calc_area_positive_float( $payload[$key . '_length'] ?? 0 ) * ad_materials_calc_area_positive_float( $payload[$key . '_width'] ?? 0 );
        }

        if ( ! empty( $surface['extra'] ) && ! empty( $payload[$key . '_extra_enabled'] ) ) {
            $areas[$key] += ad_materials_calc_preset_term_area( $surface['extra'], $key . '_extra', $payload );
        }
    }

    if ( ! empty( $config['walls_layout'] ) ) {
        if ( ( $payload['layout'] ?? '' ) === 'individual' ) {
            $areas = ['room' => 0.0];

            foreach ( array_slice( (array) ( $payload['walls'] ?? [] ), 0, 8 ) as $wall ) {
                if ( is_array( $wall ) && ! empty( $wall['enabled'] ) ) {
                    $areas['room'] += ad_materials_calc_area_positive_float( $wall['width'] ?? 0 ) * ad_materials_calc_area_positive_float( $wall['height'] ?? 0 );
                }
            }

            if ( $area === 'wall_ceiling' && ! empty( $payload['individual_ceiling_enabled'] ) ) {
                $areas['room'] += ad_materials_calc_area_positive_float( $payload['individual_ceiling_length'] ?? 0 ) * ad_materials_calc_area_positive_float( $payload['individual_ceiling_width'] ?? 0 );
            }
        }

        if ( $area === 'wall_ceiling' ) {
            foreach ( array_slice( (array) ( $payload['rooms'] ?? [] ), 0, 5 ) as $room ) {
                if ( is_array( $room ) && ! empty( $room['enabled'] ) ) {
                    $l = ad_materials_calc_area_positive_float( $room['length'] ?? 0 );
                    $w = ad_materials_calc_area_positive_float( $room['width'] ?? 0 );
                    $h = ad_materials_calc_area_positive_float( $room['height'] ?? 0 );
                    $areas['room'] += 2 * ( $l + $w ) * $h + ( ! empty( $room['ceiling'] ) ? $l * $w : 0 );
                }
            }
        }
    }

    if ( $area === 'kitchen' && ! $known && ! empty( $payload['room_enabled'] ) ) {
        $areas['backsplash'] = 0.0;
    }

    if ( $area === 'kitchen' && ( $payload['system'] ?? '' ) === 'Terrazzo Granitech Micromosaic' ) {
        return ['floor' => $areas['floor'] ?? 0.0];
    }

    return $areas;
}

function ad_materials_calc_preset_location( array $config, array $payload ) {

    $locations = array_keys( $config['locations'] ?? [] );

    return ad_materials_calc_area_choice_value( $payload['location'] ?? '', $locations, $locations[0] ?? '' );
}

function ad_materials_calc_format_preset_dimensions( array $fields, $prefix, array $payload ) {

    $values = [];
    $units  = [];
    $labels = ['ft' => __( 'ft', 'ad-materials-calculator' ), 'in' => __( 'in', 'ad-materials-calculator' )];

    foreach ( $fields as $field ) {
        $key   = $prefix !== '' ? $prefix . '_' . $field['key'] : $field['key'];
        $value = ad_materials_calc_area_positive_float( $payload[$key] ?? 0 );

        if ( $value <= 0 ) {
            return '';
        }

        $values[] = (string) $value;
        $units[]  = $labels[$field['unit']] ?? $field['unit'];
    }

    if ( empty( $values ) ) {
        return '';
    }

    if ( count( array_unique( $units ) ) === 1 ) {
        return implode( ' x ', $values ) . ' ' . $units[0];
    }

    foreach ( $values as $index => $value ) {
        $values[$index] = $value . ' ' . $units[$index];
    }

    return implode( ' x ', $values );
}

function ad_materials_calc_get_preset_project_description( $area, array $config, array $payload, array $areas ) {

    $known = ( $payload['mode'] ?? '' ) === 'known';

    if ( array_sum( $areas ) <= 0 ) {
        return $known ? __( 'Enter measured area to begin', 'ad-materials-calculator' ) : __( 'Select a surface to begin', 'ad-materials-calculator' );
    }

    if ( $known ) {
        return __( 'Measured area entered directly', 'ad-materials-calculator' );
    }

    $location    = ad_materials_calc_preset_location( $config, $payload );
    $description = '';

    if ( ! empty( $config['walls_layout'] ) ) {
        $individual = ( $payload['layout'] ?? '' ) === 'individual';
        $walls      = array_filter( array_slice( (array) ( $payload['walls'] ?? [] ), 0, 8 ), function ( $wall ) {
            return is_array( $wall ) && ! empty( $wall['enabled'] ) && ad_materials_calc_area_positive_float( $wall['width'] ?? 0 ) > 0 && ad_materials_calc_area_positive_float( $wall['height'] ?? 0 ) > 0;
        } );
        $rooms = $area === 'wall_ceiling' ? array_values( array_filter( array_slice( (array) ( $payload['rooms'] ?? [] ), 0, 5 ), function ( $room ) {
            return is_array( $room ) && ! empty( $room['enabled'] ) && ad_materials_calc_area_positive_float( $room['length'] ?? 0 ) > 0 && ad_materials_calc_area_positive_float( $room['width'] ?? 0 ) > 0 && ad_materials_calc_area_positive_float( $room['height'] ?? 0 ) > 0;
        } ) ) : [];
        $has_main = $individual ? ! empty( $walls ) || ! empty( $payload['individual_ceiling_enabled'] ) : ! empty( $payload['room_enabled'] );
        $count    = count( $rooms ) + ( $has_main ? 1 : 0 );

        if ( ! empty( $rooms ) && $count > 1 ) {
            return sprintf( __( '%d rooms - one combined kit', 'ad-materials-calculator' ), $count );
        }

        if ( ! empty( $rooms ) && ! $has_main ) {
            return sprintf( __( 'Room %s', 'ad-materials-calculator' ), ad_materials_calc_format_preset_dimensions( $config['surfaces']['room']['fields'], '', $rooms[0] ) ) . ' - ' . ( ! empty( $rooms[0]['ceiling'] ) ? __( 'ceiling included', 'ad-materials-calculator' ) : __( 'walls only', 'ad-materials-calculator' ) );
        }

        if ( $individual ) {
            if ( ! empty( $walls ) ) {
                $description = $area === 'facade' ? sprintf( _n( '%d facade elevation', '%d facade elevations', count( $walls ), 'ad-materials-calculator' ), count( $walls ) ) : sprintf( _n( '%d individual wall', '%d individual walls', count( $walls ), 'ad-materials-calculator' ), count( $walls ) );
            }

            if ( $area === 'wall_ceiling' && ! empty( $payload['individual_ceiling_enabled'] ) ) {
                $fields = array_filter( $config['surfaces']['room']['fields'], function ( $field ) {
                    return in_array( $field['key'], ['length', 'width'], true );
                } );
                $ceiling = sprintf( __( 'Ceiling %s', 'ad-materials-calculator' ), ad_materials_calc_format_preset_dimensions( $fields, 'individual_ceiling', $payload ) );
                $description .= ( $description !== '' ? ' + ' : '' ) . $ceiling;
            }

            return $description;
        }
    }

    foreach ( $config['summary_order'] ?? array_keys( $config['surfaces'] ) as $key ) {
        $surface = $config['surfaces'][$key];
        $sqft    = $areas[$key] ?? 0;

        foreach ( array_keys( $surface['parts'] ?? [] ) as $part ) {
            $sqft += $areas[$part] ?? 0;
        }

        if ( $sqft <= 0 ) {
            continue;
        }

        if ( ! empty( $surface['summary_with'] ) && ( $areas[$surface['summary_with']] ?? 0 ) > 0 ) {
            $description .= ' - ' . $surface['summary_included'];
            continue;
        }

        $source = $surface['field_source'] ?? $key;
        $fields = $config['surfaces'][$source]['fields'];
        $keys   = $surface['summary_fields'] ?? $surface['required_fields'] ?? array_column( $fields, 'key' );
        $fields = array_filter( $fields, function ( $field ) use ( $keys ) {
            return in_array( $field['key'], $keys, true );
        } );
        $label = $surface['summary_labels_by_location'][$location] ?? $surface['summary_label'] ?? $surface['label'];

        if ( ! empty( $surface['ceiling'] ) && empty( $payload[$key . '_ceiling'] ) ) {
            $label = $surface['summary_label_without_ceiling'] ?? $label;
        }

        $dimensions = ad_materials_calc_format_preset_dimensions( $fields, $source, $payload );
        $text       = $label . ( $dimensions !== '' ? ' ' . $dimensions : '' );

        if ( $description !== '' && ! empty( $surface['summary_addition'] ) ) {
            $description .= ' + ' . $surface['summary_addition'];
        } else {
            $description .= ( $description !== '' ? ' - ' : '' ) . $text;
        }

        if ( ! empty( $surface['summary_suffix'] ) ) {
            $description .= ' - ' . $surface['summary_suffix'];
        }

        if ( ! empty( $surface['extra'] ) && ! empty( $payload[$key . '_extra_enabled'] ) ) {
            $dimensions = ad_materials_calc_format_preset_dimensions( $surface['extra']['fields'], $key . '_extra', $payload );

            if ( $dimensions !== '' ) {
                $description .= ' + ' . $surface['extra']['summary_label'] . ' ' . $dimensions;
            }
        }
    }

    if ( $area === 'pool' && $location === 'deck' && ! empty( $payload['subtract_enabled'] ) ) {
        $description .= ' - ' . __( 'pool footprint excluded', 'ad-materials-calculator' );
    }

    return $description;
}

function ad_materials_calc_build_preset_result( $area, array $payload ) {

    $config   = ad_materials_calc_get_area_preset( $area );
    $location = ad_materials_calc_preset_location( $config, $payload );
    $allowed  = array_merge( ['Terrazzo Granitech Medium', 'Terrazzo Granitech Fine', 'Terrazzo Granitech Microterrazzo'], array_column( $config['finishes'] ?? [], 'value' ) );
    $system   = ad_materials_calc_area_choice_value( $payload['system'] ?? '', $allowed, $allowed[0] );

    if ( ( $payload['mode'] ?? '' ) !== 'known' ) {
        $groups = [];

        foreach ( $config['surfaces'] as $key => $surface ) {
            if ( empty( $payload[$key . '_enabled'] ) || ( isset( $surface['location'] ) && $surface['location'] !== $location ) || ( ! empty( $config['walls_layout'] ) && ( $payload['layout'] ?? '' ) === 'individual' ) || ( $area === 'kitchen' && $system === 'Terrazzo Granitech Micromosaic' && $key !== 'floor' ) || ( $area === 'kitchen' && $key === 'backsplash' && ! empty( $payload['room_enabled'] ) ) ) {
                continue;
            }

            $source                    = $surface['field_source'] ?? $key;
            $required                  = $surface['required_fields'] ?? array_column( $surface['fields'], 'key' );
            $groups[$surface['label']] = array_map( function ( $dimension ) use ( $payload, $source ) {return $payload[$source . '_' . $dimension] ?? 0;}, $required );

            if ( ! empty( $surface['extra'] ) && ! empty( $payload[$key . '_extra_enabled'] ) ) {
                $groups[$surface['extra']['label']] = array_map( function ( $field ) use ( $payload, $key ) {return $payload[$key . '_extra_' . $field['key']] ?? 0;}, $surface['extra']['fields'] );
            }
        }

        foreach ( ['walls', 'rooms'] as $repeater ) {
            if ( empty( $config['walls_layout'] ) || ( $repeater === 'rooms' && $area !== 'wall_ceiling' ) ) {
                continue;
            }

            if ( $repeater === 'walls' && ( $payload['layout'] ?? '' ) !== 'individual' ) {
                continue;
            }

            $group_label = $repeater === 'walls' ? __( 'walls%d', 'ad-materials-calculator' ) : __( 'rooms%d', 'ad-materials-calculator' );

            foreach ( (array) ( $payload[$repeater] ?? [] ) as $row ) {
                if ( is_array( $row ) && ! empty( $row['enabled'] ) ) {
                    $groups[sprintf( $group_label, count( $groups ) )] = [$row['width'] ?? 0, $row['height'] ?? 0];

                    if ( $repeater === 'rooms' ) {
                        $groups[sprintf( $group_label, count( $groups ) )] = [$row['length'] ?? 0];
                    }
                }
            }
        }

        if ( $area === 'wall_ceiling' && ( $payload['layout'] ?? '' ) === 'individual' && ! empty( $payload['individual_ceiling_enabled'] ) ) {
            $groups[__( 'Ceiling', 'ad-materials-calculator' )] = [$payload['individual_ceiling_length'] ?? 0, $payload['individual_ceiling_width'] ?? 0];
        }

        $error = ad_materials_calc_validate_area_dimension_groups( $groups );

        if ( $error ) {
            return ['error' => $error];
        }
    }

    if ( ( $area === 'floor' && $location === 'interior' && $system === 'Terrazzo Granitech Monolith' ) || ( $area === 'floor' && $location === 'exterior' && $system === 'Terrazzo Granitech Micromosaic' ) || ( $area === 'pool' && $location === 'pool' && $system === 'Terrazzo Granitech Monolith' ) ) {
        return ['error' => __( 'This finish is not available for the selected preset.', 'ad-materials-calculator' )];
    }

    $selection = ad_materials_calc_get_area_system_selection( $payload, $allowed );

    if ( ! empty( $selection['error'] ) ) {
        return $selection;
    }

    $sheen = ad_materials_calc_area_choice_value( $payload['sheen'] ?? '', $config['sheens'], $config['default_sheen'] );
    $duty  = ad_materials_calc_area_choice_value( $payload['protection'] ?? '', ['Standard Duty', 'Max Duty'], 'Standard Duty' );
    $areas = ad_materials_calc_get_preset_areas( $area, $payload );
    $calcs = [];

    if ( $area === 'pool' && $location === 'deck' && ( $areas['subtract'] ?? 0 ) > ( $areas['deck'] ?? 0 ) ) {
        return ['error' => __( 'The pool footprint cannot exceed the deck footprint.', 'ad-materials-calculator' )];
    }

    if ( $area === 'pool' && $location === 'deck' && isset( $areas['deck'] ) ) {
        $areas = ['deck' => $areas['deck'] - ( $areas['subtract'] ?? 0 )];
    }

    $project_description = ad_materials_calc_get_preset_project_description( $area, $config, $payload, $areas );
    $breakdown_rows      = [];
    $groups              = [];
    $sources             = $config['surfaces'];

    foreach ( $config['surfaces'] as $surface ) {
        $sources = array_merge( $sources, $surface['parts'] ?? [] );
    }

    if ( ( $payload['mode'] ?? '' ) === 'known' ) {
        $sources = array_merge( $sources, ad_materials_calc_preset_known_fields( $config ) );
    }

    foreach ( $areas as $key => $sqft ) {
        if ( $sqft <= 0 ) {
            continue;
        }

        $label = $sources[$key]['label'] ?? $config['title'];

        if ( $area === 'pool' && ( $payload['mode'] ?? '' ) !== 'known' ) {
            $label = $location === 'pool' ? __( 'Pool interior - walls + floor', 'ad-materials-calculator' ) : __( 'Pool deck', 'ad-materials-calculator' );
        } elseif ( ! empty( $sources[$key]['ceiling'] ) ) {
            $label = ! empty( $payload[$key . '_ceiling'] ) ? $sources[$key]['summary_label'] : $sources[$key]['summary_label_without_ceiling'];
        }

        if ( ! empty( $config['walls_layout'] ) && ( $payload['mode'] ?? '' ) !== 'known' ) {
            $individual  = ( $payload['layout'] ?? '' ) === 'individual';
            $has_ceiling = $individual ? ! empty( $payload['individual_ceiling_enabled'] ) : ! empty( $payload['room_enabled'] ) && ! empty( $payload['room_ceiling'] );
            $has_walls   = ! $individual && ! empty( $payload['room_enabled'] );

            if ( $individual ) {
                foreach ( array_slice( (array) ( $payload['walls'] ?? [] ), 0, 8 ) as $wall ) {
                    $has_walls = $has_walls || ( is_array( $wall ) && ! empty( $wall['enabled'] ) );
                }
            }

            foreach ( array_slice( (array) ( $payload['rooms'] ?? [] ), 0, 5 ) as $room ) {
                $has_ceiling = $has_ceiling || ( is_array( $room ) && ! empty( $room['enabled'] ) && ! empty( $room['ceiling'] ) );
                $has_walls   = $has_walls || ( is_array( $room ) && ! empty( $room['enabled'] ) );
            }

            $label = $area === 'facade' ? __( 'Facade walls', 'ad-materials-calculator' ) : ( ! $has_walls ? __( 'Ceiling', 'ad-materials-calculator' ) : ( $has_ceiling ? __( 'Walls + ceilings', 'ad-materials-calculator' ) : __( 'Walls', 'ad-materials-calculator' ) ) );
        }

        $breakdown_rows[] = ['label' => $label, 'area' => $sqft];
        $route            = $sources[$key]['route'] ?? $config['route'];

        if ( $area === 'floor' ) {
            $route = $location === 'exterior' ? 'exterior' : 'floor';
        } elseif ( $area === 'pool' ) {
            $route = $location === 'pool' ? 'pool' : 'exterior';
        }

        if ( ! isset( $groups[$route] ) ) {
            $groups[$route] = ['area' => 0.0, 'labels' => []];
        }

        $groups[$route]['area'] += $sqft;
        $groups[$route]['labels'][] = $sources[$key]['label'] ?? $config['title'];
    }

    foreach ( $groups as $route => $group ) {
        $answers                      = ad_materials_calc_preset_answers( $route, $group['area'], $selection, $sheen, $duty, $payload );
        $answers['pricing_area_sqft'] = array_sum( array_column( $groups, 'area' ) );
        $calc                         = ad_materials_calc_get_calculation_from_answers( $answers );

        if ( ! empty( $calc['error'] ) ) {
            return ['error' => $calc['error']];
        }

        if ( $route === 'counter' && ! empty( $config['mesh_option'] ) && empty( $payload['mesh_enabled'] ) ) {
            $calc['items'] = array_values( array_filter( $calc['items'], function ( $item ) {
                return (string) ( $item['sku'] ?? '' ) !== '5200433500619';
            } ) );
        }

        $calcs[] = ['label' => $config['route_labels'][$route] ?? implode( ' + ', $group['labels'] ), 'area' => $group['area'], 'calc' => $calc];
    }

    if ( empty( $calcs ) ) {
        return array_merge( ad_materials_calc_empty_area_result(), ['project_description' => $project_description] );
    }

    $is_monolith     = $system === 'Terrazzo Granitech Monolith';
    $is_fixed_pool   = $area === 'pool' && $location === 'pool';
    $effective_sheen = $is_monolith ? __( 'Natural stone (no sealer)', 'ad-materials-calculator' ) : ( $is_fixed_pool ? __( 'Fixed pool immersion finish', 'ad-materials-calculator' ) : ( $location === 'exterior' || $area === 'balcony' || $area === 'pool' || $system === 'Terrazzo Granitech Micromosaic' ? ad_materials_calc_get_area_sheen_label( 'Matte' ) : ad_materials_calc_get_area_sheen_label( $sheen ) ) );
    $effective_duty  = $is_monolith ? __( 'No Sealer', 'ad-materials-calculator' ) : ( $is_fixed_pool ? __( 'Fixed pool immersion build', 'ad-materials-calculator' ) : ( $duty === 'Max Duty' ? __( 'Max Duty', 'ad-materials-calculator' ) : __( 'Standard Duty', 'ad-materials-calculator' ) ) );

    $meta = [
        $project_description,
        sprintf( __( 'System: %s', 'ad-materials-calculator' ), $system ),
        sprintf( __( 'Pigment: %s', 'ad-materials-calculator' ), $selection['pigment'] === 'Unpigmented' ? __( 'Unpigmented', 'ad-materials-calculator' ) : $selection['pigment'] ),
        sprintf( __( 'Stone: %s', 'ad-materials-calculator' ), $selection['stone'] ?? '' ),
        sprintf( __( 'Blend: %s', 'ad-materials-calculator' ), $selection['finish'] ),
        sprintf( __( 'Sheen: %s', 'ad-materials-calculator' ), $effective_sheen ),
        sprintf( __( 'Protection: %s', 'ad-materials-calculator' ), $effective_duty ),
    ];

    if ( ! empty( $config['mesh_option'] ) ) {
        $meta[] = sprintf( __( 'Fiberglass mesh: %s', 'ad-materials-calculator' ), ! empty( $payload['mesh_enabled'] ) ? __( 'Included', 'ad-materials-calculator' ) : __( 'Not included', 'ad-materials-calculator' ) );
    }

    $result                        = ad_materials_calc_build_area_result( $config['project_label'] ?? $config['title'], $system, $calcs, $meta, $breakdown_rows );
    $result['project_description'] = $project_description;

    return $result;
}

function ad_materials_calc_preset_answers( $route, $sqft, array $selection, $sheen, $duty, array $payload ) {

    $answers = [
        'total_area_sqft' => $sqft, 'surface_type_field_id'        => 2,
        'terrazzo_system' => $selection['system'], 'pigment_color' => $selection['pigment'],
        'finish_color'    => $selection['finish'], 'stone'         => $selection['stone'] ?? '',
        'discount_type'   => 'none', 'discount_label'              => __( 'No discount', 'ad-materials-calculator' ), 'discount_allowed' => false,
    ];

    if ( $route === 'wet_wall' ) {
        $answers += ['surface_type' => 'Wet Area Walls', 'existing_tile_wet_area' => 'No', 'tile_joint_condition_wet_area' => 'Close joints with regular thinset', 'sheen_wet_area' => $sheen === 'Matte' ? 'Matte Dual-Layer' : $sheen, 'usage_level_wet_area' => $duty];
    } elseif ( $route === 'wall' || $route === 'facade' ) {
        $answers += ['surface_type' => 'Interior / Exterior Walls', 'substrate_type_wall' => $route === 'facade' && ! empty( $payload['mesh_enabled'] ) ? 'Other substrates' : 'Interior drywall (Level 4 or 5) or similarly smooth/stable substrates', 'sheen_wall' => $sheen, 'usage_level_wall' => $duty];

        if ( $selection['system'] === 'Terrazzo Granitech Gaia' ) {
            $answers['surface_type_field_id']     = 48;
            $answers['surface_type']              = 'Interior / Pre-Painted Drywall';
            $answers['sheen_gaia_interior']       = $sheen;
            $answers['usage_level_gaia_interior'] = $duty;
        }
    } elseif ( $route === 'counter' ) {
        $answers += ['surface_type' => 'Countertop / Vanity', 'sheen_countertop_vanity' => $sheen, 'usage_level_countertop_vanity' => $duty];
    } elseif ( $route === 'pool' ) {
        $answers['surface_type'] = 'Pool Interior';
    } elseif ( $route === 'exterior' ) {
        $answers += ['surface_type' => 'Exterior Floor (incl. Pool Decks)', 'usage_level_exterior_floor' => $duty];

        if ( $selection['system'] === 'Terrazzo Granitech Monolith' ) {
            $answers['surface_type_field_id'] = 57;
            $answers['surface_type']          = 'Exterior Floor / Foot Traffic (Walkways, Patios)';
            $answers['sealer_level_monolith'] = 'No Sealer';
            $answers['sheen_monolith']        = '';
        } elseif ( $selection['system'] === 'Terrazzo Granitech Micromosaic' ) {
            $answers['surface_type_field_id'] = 58;
            $answers['surface_type']          = 'Exterior Floor';
        }
    } else {
        $answers += ['surface_type' => 'Interior Floor', 'moisture_exposure_interior_floor' => $route === 'wet_floor' ? 'Wet Area' : 'Dry Area', 'sheen_interior_floor' => $route === 'wet_floor' || $selection['system'] === 'Terrazzo Granitech Micromosaic' ? 'Matte' : $sheen, 'usage_level_interior_floor' => $duty];

        if ( $route === 'wet_floor' ) {
            $answers += ['existing_tile_interior_floor' => 'No', 'tile_joint_condition_interior_floor' => 'Close joints with regular thinset'];
        }

        if ( $selection['system'] === 'Terrazzo Granitech Micromosaic' ) {
            $answers['surface_type_field_id'] = 58;
        }
    }

    return $answers;
}