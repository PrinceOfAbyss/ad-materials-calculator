<?php
/**
 * Materials Calculator - Case 7
 * Interior / Pre-Painted Drywall
 *
 * Notes:
 * - Handles Case 7 output rendering
 * - Validates the Case 7 form answers
 * - Builds the required material line items for Interior / Pre-Painted Drywall
 * - Returns the clause/debug data and calculated products for this case
 */

/**
 * Case 7 - Output
 */
function ad_materials_calc_case_7_output( array $answers ) {

    return ad_materials_calc_render_calculation_result( ad_materials_calc_get_calculation_from_answers( $answers ) );
}

/**
 * Case 7 Build line items
 */
function ad_materials_calc_case_7_line_items( array $answers ) {

    $sqft = isset( $answers['total_area_sqft'] ) ? (float) $answers['total_area_sqft'] : 0;

    if ( $sqft <= 0 ) {
        return ['error' => 'Case 7 error: Total surface area is missing or invalid.'];
    }

    $sqm = round( $sqft / 10.76 * 1.1, 1 );

    $sheen         = isset( $answers['sheen_gaia_interior'] ) ? (string) $answers['sheen_gaia_interior'] : '';
    $usage         = isset( $answers['usage_level_gaia_interior'] ) ? (string) $answers['usage_level_gaia_interior'] : '';
    $system_label  = isset( $answers['terrazzo_system'] ) ? (string) $answers['terrazzo_system'] : '';
    $pigment_label = isset( $answers['pigment_color'] ) ? (string) $answers['pigment_color'] : '';
    $finish_color  = isset( $answers['finish_color'] ) ? (string) $answers['finish_color'] : '';

    if ( $sheen !== 'Matte' && $sheen !== 'Semi-Gloss' && $sheen !== 'Satin' ) {
        return ['error' => 'Case 7 error: Unknown "Sheen" value: ' . $sheen];
    }

    if ( $usage !== 'Standard Duty' && $usage !== 'Max Duty' ) {
        return ['error' => 'Case 7 error: Unknown "Usage Level" value: ' . $usage];
    }

    $code = '';

    if ( $sheen === 'Matte' && $usage === 'Standard Duty' ) {
        $code = 'matte_standard';
    } elseif ( $sheen === 'Matte' && $usage === 'Max Duty' ) {
        $code = 'matte_max';
    } elseif ( $sheen === 'Semi-Gloss' && $usage === 'Standard Duty' ) {
        $code = 'semigloss_standard';
    } elseif ( $sheen === 'Semi-Gloss' && $usage === 'Max Duty' ) {
        $code = 'semigloss_max';
    } elseif ( $sheen === 'Satin' && $usage === 'Standard Duty' ) {
        $code = 'satin_standard';
    } elseif ( $sheen === 'Satin' && $usage === 'Max Duty' ) {
        $code = 'satin_max';
    }

    if ( $code === '' ) {
        return ['error' => 'Case 7 error: No matching clause found for the given selections.'];
    }

    $clause_key = $code;
    $clause     = 'case_7:' . $clause_key;

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
    // System(s)
    // -----------------------------------------------------
    $system_counters = [
        'gaia_pail'   => 0,
        'gaia_gallon' => 0,
        'gaia_quart'  => 0,
    ];

    if ( $system_label === 'Terrazzo Granitech Gaia' ) {
        $gaia_config = ad_materials_calc_get_configured_coverage_values( 'case_7', $clause_key, $system_label, 'system_gaia', 'Case 7 error:' );

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
        return ['error' => 'Case 7 error: Unknown System value: ' . $system_label];
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
                return ['error' => 'Case 7 error: Could not resolve the selected ' . ucfirst( $pigment_family ) . ' pigment variation "' . $pigment_label . '".'];
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
    if ( $code === 'matte_standard' ) {
        $matte_small_project_split = ( $sqft <= 270 );

        if ( $matte_small_project_split ) {
            $sealer_qty = (int) ceil( $sqft / 135 );

            $debug['lines'][] = [
                'line'                => 'Sealer a1 - Anti-Slip Matte pair',
                'sqft'                => $sqft,
                'sqft_per_1kg_pair'   => 135,
                'small_project_split' => 270,
                'qty'                 => $sealer_qty,
                'sku_a'               => '5200433501785',
                'sku_b'               => '5200433501786',
                'unit_price_a'        => ad_materials_calc_get_product_price_by_sku( '5200433501785' ),
                'unit_price_b'        => ad_materials_calc_get_product_price_by_sku( '5200433501786' ),
            ];

            $sealer_product    = ad_materials_calc_get_product_by_sku( '5200433501785' );
            $activator_product = ad_materials_calc_get_product_by_sku( '5200433501786' );

            if ( ! $sealer_product || ! $activator_product ) {
                return ['error' => 'Case 7 error: Could not resolve 1kg Anti-Slip Matte sealer pair.'];
            }
        } else {
            $sealer_config = ad_materials_calc_get_configured_coverage_values( 'case_7', $clause_key, $system_label, 'sealer_matte', 'Case 7 error:' );

            if ( ! empty( $sealer_config['error'] ) ) {
                return ['error' => $sealer_config['error']];
            }

            $sealer_components = $sealer_config['values'];
            $sealer_kg         = round( $sqm * array_sum( $sealer_components ), 2 );
            $sealer_qty        = (int) ceil( $sealer_kg / 3 );

            $debug['lines'][] = [
                'line'               => 'Sealer a1 - XMATTE A+B',
                'coverage_kg_per_m2' => $sealer_components,
                'sqm'                => $sqm,
                'required_kg'        => $sealer_kg,
                'bucket_size'        => 3,
                'qty'                => $sealer_qty,
                'sku_a'              => '5200433500565',
                'sku_b'              => '5200433500572',
                'unit_price_a'       => ad_materials_calc_get_product_price_by_sku( '5200433500565' ),
                'unit_price_b'       => ad_materials_calc_get_product_price_by_sku( '5200433500572' ),
            ];

            $sealer_product    = ad_materials_calc_get_product_by_sku( '5200433500565' );
            $activator_product = ad_materials_calc_get_product_by_sku( '5200433500572' );

            if ( ! $sealer_product || ! $activator_product ) {
                return ['error' => 'Case 7 error: Could not resolve Matte sealer / activator SKUs.'];
            }
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $sealer_product, $sealer_qty );
        $items[] = ad_materials_calc_build_line_item_from_product( $activator_product, $sealer_qty );
    }

    if ( $code === 'matte_max' ) {
        $matte_small_project_split = ( $sqft <= 135 );

        if ( $matte_small_project_split ) {
            $sealer_qty = (int) ceil( $sqft / 67.5 );

            $debug['lines'][] = [
                'line'                => 'Sealer a2 - Anti-Slip Matte pair',
                'sqft'                => $sqft,
                'sqft_per_1kg_pair'   => 67.5,
                'small_project_split' => 135,
                'qty'                 => $sealer_qty,
                'sku_a'               => '5200433501785',
                'sku_b'               => '5200433501786',
                'unit_price_a'        => ad_materials_calc_get_product_price_by_sku( '5200433501785' ),
                'unit_price_b'        => ad_materials_calc_get_product_price_by_sku( '5200433501786' ),
            ];

            $sealer_product    = ad_materials_calc_get_product_by_sku( '5200433501785' );
            $activator_product = ad_materials_calc_get_product_by_sku( '5200433501786' );

            if ( ! $sealer_product || ! $activator_product ) {
                return ['error' => 'Case 7 error: Could not resolve 1kg Anti-Slip Matte sealer pair.'];
            }
        } else {
            $sealer_config = ad_materials_calc_get_configured_coverage_values( 'case_7', $clause_key, $system_label, 'sealer_matte', 'Case 7 error:' );

            if ( ! empty( $sealer_config['error'] ) ) {
                return ['error' => $sealer_config['error']];
            }

            $sealer_components = $sealer_config['values'];
            $sealer_kg         = round( $sqm * array_sum( $sealer_components ), 2 );
            $sealer_qty        = (int) ceil( $sealer_kg / 3 );

            $debug['lines'][] = [
                'line'               => 'Sealer a2 - XMATTE A+B',
                'coverage_kg_per_m2' => $sealer_components,
                'sqm'                => $sqm,
                'required_kg'        => $sealer_kg,
                'bucket_size'        => 3,
                'qty'                => $sealer_qty,
                'sku_a'              => '5200433500565',
                'sku_b'              => '5200433500572',
                'unit_price_a'       => ad_materials_calc_get_product_price_by_sku( '5200433500565' ),
                'unit_price_b'       => ad_materials_calc_get_product_price_by_sku( '5200433500572' ),
            ];

            $sealer_product    = ad_materials_calc_get_product_by_sku( '5200433500565' );
            $activator_product = ad_materials_calc_get_product_by_sku( '5200433500572' );

            if ( ! $sealer_product || ! $activator_product ) {
                return ['error' => 'Case 7 error: Could not resolve Matte sealer / activator SKUs.'];
            }
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $sealer_product, $sealer_qty );
        $items[] = ad_materials_calc_build_line_item_from_product( $activator_product, $sealer_qty );
    }

    if ( $code === 'semigloss_standard' ) {
        $sealer_config = ad_materials_calc_get_configured_coverage_values( 'case_7', $clause_key, $system_label, 'sealer_semigloss', 'Case 7 error:' );

        if ( ! empty( $sealer_config['error'] ) ) {
            return ['error' => $sealer_config['error']];
        }

        $sealer_components = $sealer_config['values'];
        $sealer_kg         = round( $sqm * array_sum( $sealer_components ), 2 );
        $sealer_qty        = (int) ceil( $sealer_kg / 5 );

        $debug['lines'][] = [
            'line'               => 'Sealer b1 - Semi-Gloss',
            'coverage_kg_per_m2' => $sealer_components,
            'sqm'                => $sqm,
            'required_kg'        => $sealer_kg,
            'bucket_size'        => 5,
            'qty'                => $sealer_qty,
            'sku'                => '5200433500589',
            'unit_price'         => ad_materials_calc_get_product_price_by_sku( '5200433500589' ),
        ];

        $semi_product = ad_materials_calc_get_product_by_sku( '5200433500589' );

        if ( ! $semi_product ) {
            return ['error' => 'Case 7 error: Could not resolve Semi-Gloss SKU 5200433500589.'];
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $semi_product, $sealer_qty );
    }

    if ( $code === 'semigloss_max' ) {
        $sealer_config = ad_materials_calc_get_configured_coverage_values( 'case_7', $clause_key, $system_label, 'sealer_semigloss', 'Case 7 error:' );

        if ( ! empty( $sealer_config['error'] ) ) {
            return ['error' => $sealer_config['error']];
        }

        $sealer_components = $sealer_config['values'];
        $sealer_kg         = round( $sqm * array_sum( $sealer_components ), 2 );
        $sealer_qty        = (int) ceil( $sealer_kg / 5 );

        $debug['lines'][] = [
            'line'               => 'Sealer b2 - Semi-Gloss',
            'coverage_kg_per_m2' => $sealer_components,
            'sqm'                => $sqm,
            'required_kg'        => $sealer_kg,
            'bucket_size'        => 5,
            'qty'                => $sealer_qty,
            'sku'                => '5200433500589',
            'unit_price'         => ad_materials_calc_get_product_price_by_sku( '5200433500589' ),
        ];

        $semi_product = ad_materials_calc_get_product_by_sku( '5200433500589' );

        if ( ! $semi_product ) {
            return ['error' => 'Case 7 error: Could not resolve Semi-Gloss SKU 5200433500589.'];
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $semi_product, $sealer_qty );
    }

    if ( $code === 'satin_standard' ) {
        $sealer_config = ad_materials_calc_get_configured_coverage_values( 'case_7', $clause_key, $system_label, 'sealer_satin', 'Case 7 error:' );

        if ( ! empty( $sealer_config['error'] ) ) {
            return ['error' => $sealer_config['error']];
        }

        $sealer_components = $sealer_config['values'];
        $sealer_kg         = round( $sqm * array_sum( $sealer_components ), 2 );

        $satin_bucket_prices = [
            10 => ad_materials_calc_get_product_price_by_sku( '5200433500053' ),
            5  => ad_materials_calc_get_product_price_by_sku( '5200433500046' ),
            1  => ad_materials_calc_get_product_price_by_sku( '5200433501812' ),
        ];

        $satin_combo = ad_materials_calc_get_best_3bucket_combo( $sealer_kg, $satin_bucket_prices );

        $debug['lines'][] = [
            'line'               => 'Sealer c1 - Satin',
            'coverage_kg_per_m2' => $sealer_components,
            'sqm'                => $sqm,
            'required_kg'        => $sealer_kg,
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

    if ( $code === 'satin_max' ) {
        $sealer_config = ad_materials_calc_get_configured_coverage_values( 'case_7', $clause_key, $system_label, 'sealer_satin', 'Case 7 error:' );

        if ( ! empty( $sealer_config['error'] ) ) {
            return ['error' => $sealer_config['error']];
        }

        $sealer_components = $sealer_config['values'];
        $sealer_kg         = round( $sqm * array_sum( $sealer_components ), 2 );

        $satin_bucket_prices = [
            10 => ad_materials_calc_get_product_price_by_sku( '5200433500053' ),
            5  => ad_materials_calc_get_product_price_by_sku( '5200433500046' ),
            1  => ad_materials_calc_get_product_price_by_sku( '5200433501812' ),
        ];

        $satin_combo = ad_materials_calc_get_best_3bucket_combo( $sealer_kg, $satin_bucket_prices );

        $debug['lines'][] = [
            'line'               => 'Sealer c2 - Satin',
            'coverage_kg_per_m2' => $sealer_components,
            'sqm'                => $sqm,
            'required_kg'        => $sealer_kg,
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

    return [
        'clause' => $clause,
        'sqm'    => $sqm,
        'items'  => $items,
        'debug'  => $debug,
    ];
}