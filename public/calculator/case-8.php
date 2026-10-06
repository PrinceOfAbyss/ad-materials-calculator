<?php
/**
 * Materials Calculator - Case 8
 * Wet Areas & Exterior Walls
 *
 * Notes:
 * - Handles Case 8 output rendering
 * - Validates the Case 8 form answers
 * - Builds the required material line items for Wet Areas & Exterior Walls
 * - Returns the clause/debug data and calculated products for this case
 */

/**
 * Case 8 - Output
 */
function ad_materials_calc_case_8_output( array $answers ) {

    return ad_materials_calc_render_calculation_result( ad_materials_calc_get_calculation_from_answers( $answers ) );
}

/**
 * Case 8 Build line items
 */
function ad_materials_calc_case_8_line_items( array $answers ) {

    $sqft = isset( $answers['total_area_sqft'] ) ? (float) $answers['total_area_sqft'] : 0;

    if ( $sqft <= 0 ) {
        return ['error' => 'Case 8 error: Total surface area is missing or invalid.'];
    }

    $sqm = round( $sqft / 10.76 * 1.1, 1 );

    $sheen         = isset( $answers['sheen_gaia_wet_area'] ) ? (string) $answers['sheen_gaia_wet_area'] : '';
    $usage         = isset( $answers['usage_level_gaia_wet_area'] ) ? (string) $answers['usage_level_gaia_wet_area'] : '';
    $system_label  = isset( $answers['terrazzo_system'] ) ? (string) $answers['terrazzo_system'] : '';
    $pigment_label = isset( $answers['pigment_color'] ) ? (string) $answers['pigment_color'] : '';
    $finish_color  = isset( $answers['finish_color'] ) ? (string) $answers['finish_color'] : '';

    if ( $sheen !== 'Matte Dual-Layer' && $sheen !== 'Matte Single-Layer' && $sheen !== 'Satin' ) {
        return ['error' => 'Case 8 error: Unknown "Sheen" value: ' . $sheen];
    }

    if ( $usage !== 'Minimal Duty' && $usage !== 'Standard Duty' && $usage !== 'Max Duty' ) {
        return ['error' => 'Case 8 error: Unknown "Usage Level" value: ' . $usage];
    }

    $code = '';

    if ( $sheen === 'Matte Dual-Layer' && $usage === 'Max Duty' ) {
        $code = 'matte_dual_max';
    } elseif ( $sheen === 'Matte Dual-Layer' && $usage === 'Standard Duty' ) {
        $code = 'matte_dual_standard';
    } elseif ( $sheen === 'Matte Single-Layer' && $usage === 'Standard Duty' ) {
        $code = 'matte_single_standard';
    } elseif ( $sheen === 'Satin' && $usage === 'Max Duty' ) {
        $code = 'satin_max';
    } elseif ( $sheen === 'Satin' && $usage === 'Standard Duty' ) {
        $code = 'satin_standard';
    } elseif ( $sheen === 'Satin' && $usage === 'Minimal Duty' ) {
        $code = 'satin_minimal';
    }

    if ( $code === '' ) {
        return ['error' => 'Case 8 error: No matching clause found for the given selections.'];
    }

    $clause_key = $code;
    $clause     = 'case_8:' . $clause_key;

    $items = [];

    $debug = [
        'sqft'         => $sqft,
        'sqm'          => $sqm,
        'system_label' => $system_label,
        'pigment'      => $pigment_label,
        'finish_color' => $finish_color,
        'clause_code'  => $code,
        'lines'        => [],
    ];

    // -----------------------------------------------------
    // Fibermesh
    // -----------------------------------------------------
    $mesh_product = ad_materials_calc_get_product_by_sku( '5200433500619' );

    if ( ! $mesh_product ) {
        return ['error' => 'Case 8 error: Could not resolve Fibermesh SKU 5200433500619.'];
    }

    $mesh_qty = (int) ceil( $sqm / 50 );

    $debug['lines'][] = [
        'line'         => 'Fibermesh',
        'rule'         => 'Case 8 always adds fibermesh: ceil(sqm / 50)',
        'sqm'          => $sqm,
        'required_sqm' => ceil( $sqm ),
        'mesh_qty'     => $mesh_qty,
        'sku'          => '5200433500619',
        'unit_price'   => ad_materials_calc_get_product_price_by_sku( '5200433500619' ),
    ];

    if ( $mesh_qty > 0 ) {
        $items[] = ad_materials_calc_build_line_item_from_product( $mesh_product, $mesh_qty );
    }

    // -----------------------------------------------------
    // Primer
    // -----------------------------------------------------
    $primer_config = ad_materials_calc_get_configured_coverage_values( 'case_8', $clause_key, $system_label, 'primer', 'Case 8 error:' );

    if ( ! empty( $primer_config['error'] ) ) {
        return ['error' => $primer_config['error']];
    }

    $primer_components    = $primer_config['values'];
    $primer_kg            = round( $sqm * array_sum( $primer_components ), 2 );
    $primer_bucket_prices = [
        15 => ad_materials_calc_get_product_price_by_sku( '5200433500541' ),
        //3  => ad_materials_calc_get_product_price_by_sku( '5200433501811' ),
        1  => ad_materials_calc_get_product_price_by_sku( '5200433501813' ),
    ];

    $primer_combo = ad_materials_calc_get_best_2bucket_combo( $primer_kg, $primer_bucket_prices );

    $debug['lines'][] = [
        'line'               => 'Primer',
        'coverage_kg_per_m2' => $primer_components,
        'sqm'                => $sqm,
        'required_kg'        => $primer_kg,
        'bucket_prices'      => $primer_bucket_prices,
        'combo_result'       => $primer_combo,
        'sku_map'            => [
            15 => '5200433500541',
            //3  => '5200433501811',
            1  => '5200433501813',
        ],
    ];

    $items = array_merge(
        $items,
        ad_materials_calc_expand_combo_to_line_items(
            $primer_combo,
            [
                15 => '5200433500541',
                //3  => '5200433501811',
                1  => '5200433501813',
            ]
        )
    );

    // -----------------------------------------------------
    // System(s)
    // -----------------------------------------------------
    $system_counters = [
        'gaia_pail'   => 0,
        'gaia_gallon' => 0,
        'gaia_quart'  => 0,
    ];

    if ( $system_label === 'Terrazzo Granitech Gaia' ) {
        $gaia_config = ad_materials_calc_get_configured_coverage_values( 'case_8', $clause_key, $system_label, 'system_gaia', 'Case 8 error:' );

        if ( ! empty( $gaia_config['error'] ) ) {
            return ['error' => $gaia_config['error']];
        }

        $gaia_components = $gaia_config['values'];

        $gaia_kg = round( $sqm * array_sum( $gaia_components ), 2 );

        // Half-kilogram units keep the 1.5kg quart as an exact integer optimizer key
        $gaia_weight_unit_kg = 0.5;
        $gaia_target_units   = $gaia_kg / $gaia_weight_unit_kg;

        $gaia_bucket_prices = [
            30 => ad_materials_calc_get_product_price_by_sku( '5200433501809' ),
            12 => ad_materials_calc_get_product_price_by_sku( '5200433501949' ),
            3  => ad_materials_calc_get_product_price_by_sku( '5200433501948' ),
        ];

        $gaia_combo      = ad_materials_calc_get_best_3bucket_combo( $gaia_target_units, $gaia_bucket_prices );
        $gaia_pail_qty   = ad_materials_calc_get_combo_bucket_count( $gaia_combo, 30 );
        $gaia_gallon_qty = ad_materials_calc_get_combo_bucket_count( $gaia_combo, 12 );
        $gaia_quart_qty  = ad_materials_calc_get_combo_bucket_count( $gaia_combo, 3 );

        $debug['lines'][] = [
            'line'                  => 'System - Gaia',
            'coverage_kg_per_m2'    => $gaia_components,
            'sqm'                   => $sqm,
            'required_kg'           => $gaia_kg,
            'bucket_weight_unit_kg' => $gaia_weight_unit_kg,
            'bucket_prices'         => $gaia_bucket_prices,
            'combo_result'          => $gaia_combo,
            'resolved_qty'          => [
                30 => $gaia_pail_qty,
                12 => $gaia_gallon_qty,
                3  => $gaia_quart_qty,
            ],
            'sku_map'               => [
                30 => '5200433501809',
                12 => '5200433501949',
                3  => '5200433501948',
            ],
        ];

        $items = array_merge(
            $items,
            ad_materials_calc_expand_combo_to_line_items(
                $gaia_combo,
                [
                    30 => '5200433501809',
                    12 => '5200433501949',
                    3  => '5200433501948',
                ]
            )
        );

        $system_counters['gaia_pail']   = $gaia_pail_qty;
        $system_counters['gaia_gallon'] = $gaia_gallon_qty;
        $system_counters['gaia_quart']  = $gaia_quart_qty;
    } else {
        return ['error' => 'Case 8 error: Unknown System value: ' . $system_label];
    }

    // -----------------------------------------------------
    // Colorant
    // -----------------------------------------------------
    if ( ! ad_materials_calc_is_unpigmented( $pigment_label ) ) {
        $colorant_data = ad_materials_calc_get_colorant_line_data( $system_label, $system_counters );

        $debug['lines'][] = [
            'line'                => 'Colorant',
            'pigment_label'       => $pigment_label,
            'system_counters'     => $system_counters,
            'colorant_lines'      => $colorant_data['lines'],
            'gaia_small_fraction' => $colorant_data['gaia_small_fraction'],
        ];

        foreach ( $colorant_data['lines'] as $colorant_line ) {
            $pigment_family  = isset( $colorant_line['pigment_family'] ) ? (string) $colorant_line['pigment_family'] : '';
            $colorant_qty    = isset( $colorant_line['qty'] ) ? (int) $colorant_line['qty'] : 0;
            $pigment_product = ad_materials_calc_find_pigment_product_by_family_label( $pigment_family, $pigment_label );

            if ( ! $pigment_product ) {
                return ['error' => 'Case 8 error: Could not resolve the selected ' . ucfirst( $pigment_family ) . ' pigment variation "' . $pigment_label . '".'];
            }

            $debug['lines'][] = [
                'line'          => 'Colorant - ' . ucfirst( $pigment_family ),
                'pigment_label' => $pigment_label,
                'qty'           => $colorant_qty,
                'pail_count'    => isset( $colorant_line['pail_count'] ) ? (int) $colorant_line['pail_count'] : 0,
                'gallon_count'  => isset( $colorant_line['gallon_count'] ) ? (int) $colorant_line['gallon_count'] : 0,
                'quart_count'   => isset( $colorant_line['quart_count'] ) ? (int) $colorant_line['quart_count'] : 0,
                'variation_sku' => $pigment_product->get_sku(),
                'unit_price'    => ad_materials_calc_get_calculator_product_unit_price( $pigment_product ),
            ];

            if ( $colorant_qty > 0 ) {
                $items[] = ad_materials_calc_build_line_item_from_product( $pigment_product, $colorant_qty );
            }
        }
    } else {
        $debug['lines'][] = [
            'line'          => 'Colorant',
            'pigment_label' => $pigment_label,
            'skipped'       => true,
            'reason'        => 'Unpigmented selected',
        ];
    }

    // -----------------------------------------------------
    // Sealer(s)
    // -----------------------------------------------------
    $satin_components = [];
    $matte_components = [];

    if ( in_array( $code, ['matte_dual_max', 'matte_dual_standard', 'satin_max', 'satin_standard', 'satin_minimal'], true ) ) {
        $satin_config = ad_materials_calc_get_configured_coverage_values( 'case_8', $clause_key, $system_label, 'sealer_satin', 'Case 8 error:' );

        if ( ! empty( $satin_config['error'] ) ) {
            return ['error' => $satin_config['error']];
        }

        $satin_components = $satin_config['values'];
    }

    if ( in_array( $code, ['matte_dual_max', 'matte_dual_standard', 'matte_single_standard'], true ) ) {
        $matte_config = ad_materials_calc_get_configured_coverage_values( 'case_8', $clause_key, $system_label, 'sealer_matte', 'Case 8 error:' );

        if ( ! empty( $matte_config['error'] ) ) {
            return ['error' => $matte_config['error']];
        }

        $matte_components = $matte_config['values'];
    }

    if ( array_sum( $satin_components ) > 0 ) {
        $satin_kg = round( $sqm * array_sum( $satin_components ), 2 );

        $satin_bucket_prices = [
            10 => ad_materials_calc_get_product_price_by_sku( '5200433500053' ),
            5  => ad_materials_calc_get_product_price_by_sku( '5200433500046' ),
            1  => ad_materials_calc_get_product_price_by_sku( '5200433501812' ),
        ];

        $satin_combo = ad_materials_calc_get_best_3bucket_combo( $satin_kg, $satin_bucket_prices );

        $debug['lines'][] = [
            'line'               => 'Sealer - Satin',
            'coverage_kg_per_m2' => $satin_components,
            'sqm'                => $sqm,
            'required_kg'        => $satin_kg,
            'bucket_prices'      => $satin_bucket_prices,
            'combo_result'       => $satin_combo,
            'sku_map'            => [
                10 => '5200433500053',
                5  => '5200433500046',
                1  => '5200433501812',
            ],
        ];

        $items = array_merge(
            $items,
            ad_materials_calc_expand_combo_to_line_items(
                $satin_combo,
                [
                    10 => '5200433500053',
                    5  => '5200433500046',
                    1  => '5200433501812',
                ]
            )
        );
    }

    if ( array_sum( $matte_components ) > 0 ) {
        $matte_kg = round( $sqm * array_sum( $matte_components ), 2 );

        $matte_bucket_prices = [
            5 => ad_materials_calc_get_product_price_by_sku( 'Armour Coat' ) + ad_materials_calc_get_product_price_by_sku( '5200433501685' ),
            1 => ad_materials_calc_get_product_price_by_sku( '5200433501785' ) + ad_materials_calc_get_product_price_by_sku( '5200433501786' ),
        ];

        $matte_combo = ad_materials_calc_get_best_2bucket_combo( $matte_kg, $matte_bucket_prices );

        $debug['lines'][] = [
            'line'               => 'Sealer - Matte pair',
            'coverage_kg_per_m2' => $matte_components,
            'sqm'                => $sqm,
            'required_kg'        => $matte_kg,
            'bucket_prices'      => $matte_bucket_prices,
            'combo_result'       => $matte_combo,
            'sku_map'            => [
                '5kg_sealer'    => 'Armour Coat',
                '5kg_activator' => '5200433501685',
                '1kg_sealer'    => '5200433501785',
                '1kg_activator' => '5200433501786',
            ],
        ];

        $matte_5kg_qty = ad_materials_calc_get_combo_bucket_count( $matte_combo, 5 );
        $matte_1kg_qty = ad_materials_calc_get_combo_bucket_count( $matte_combo, 1 );

        if ( $matte_5kg_qty > 0 ) {
            $matte_5kg_sealer_product = ad_materials_calc_get_product_by_sku( 'Armour Coat' );
            $matte_5kg_activator      = ad_materials_calc_get_product_by_sku( '5200433501685' );

            if ( ! $matte_5kg_sealer_product || ! $matte_5kg_activator ) {
                return ['error' => 'Case 8 error: Could not resolve 5kg matte sealer pair.'];
            }

            $items[] = ad_materials_calc_build_line_item_from_product( $matte_5kg_sealer_product, $matte_5kg_qty );
            $items[] = ad_materials_calc_build_line_item_from_product( $matte_5kg_activator, $matte_5kg_qty );
        }

        if ( $matte_1kg_qty > 0 ) {
            $matte_1kg_sealer_product = ad_materials_calc_get_product_by_sku( '5200433501785' );
            $matte_1kg_activator      = ad_materials_calc_get_product_by_sku( '5200433501786' );

            if ( ! $matte_1kg_sealer_product || ! $matte_1kg_activator ) {
                return ['error' => 'Case 8 error: Could not resolve 1kg matte sealer pair.'];
            }

            $items[] = ad_materials_calc_build_line_item_from_product( $matte_1kg_sealer_product, $matte_1kg_qty );
            $items[] = ad_materials_calc_build_line_item_from_product( $matte_1kg_activator, $matte_1kg_qty );
        }
    }

    return [
        'clause' => $clause,
        'sqm'    => $sqm,
        'items'  => $items,
        'debug'  => $debug,
    ];
}