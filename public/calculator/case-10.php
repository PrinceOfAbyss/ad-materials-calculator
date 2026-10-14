<?php
/**
 * Materials Calculator - Case 10
 * Exterior Floor / Car Traffic (Driveways)
 *
 * Notes:
 * - Handles Case 10 output rendering
 * - Validates the Case 10 form answers
 * - Builds the required material line items for Monolith Car Traffic
 * - Returns the clause/debug data and calculated products for this case
 */

/**
 * Case 10 - Output
 */
function ad_materials_calc_case_10_output( array $answers ) {

    return ad_materials_calc_render_calculation_result( ad_materials_calc_get_calculation_from_answers( $answers ) );
}

/**
 * Case 10 Build line items
 */
function ad_materials_calc_case_10_line_items( array $answers ) {

    $sqft = isset( $answers['total_area_sqft'] ) ? (float) $answers['total_area_sqft'] : 0;

    if ( $sqft <= 0 ) {
        return ['error' => 'Case 10 error: Total surface area is missing or invalid.'];
    }

    $sqm = round( $sqft / 10.76 * 1.1, 1 );

    $system_label = isset( $answers['terrazzo_system'] ) ? (string) $answers['terrazzo_system'] : '';
    $stone_label  = isset( $answers['stone'] ) ? (string) $answers['stone'] : '';
    $sealer_level = isset( $answers['sealer_level_monolith'] ) ? (string) $answers['sealer_level_monolith'] : '';
    $sheen        = isset( $answers['sheen_monolith'] ) ? (string) $answers['sheen_monolith'] : '';

    if ( $system_label !== 'Terrazzo Granitech Monolith' ) {
        return ['error' => 'Case 10 error: Unknown System value: ' . $system_label];
    }

    if ( $sealer_level !== 'No Sealer' && $sealer_level !== 'Standard Duty' && $sealer_level !== 'Max Duty' ) {
        return ['error' => 'Case 10 error: Unknown "Sealer Protection" value: ' . $sealer_level];
    }

    if ( $sealer_level !== 'No Sealer' && $sheen !== 'Matte' && $sheen !== 'Satin' ) {
        return ['error' => 'Case 10 error: Unknown "Sheen" value: ' . $sheen];
    }

    $code = '';

    if ( $sealer_level === 'No Sealer' ) {
        $code = 'no_sealer';
    } elseif ( $sheen === 'Matte' && $sealer_level === 'Max Duty' ) {
        $code = 'matte_max';
    } elseif ( $sheen === 'Matte' && $sealer_level === 'Standard Duty' ) {
        $code = 'matte_standard';
    } elseif ( $sheen === 'Satin' && $sealer_level === 'Max Duty' ) {
        $code = 'satin_max';
    } elseif ( $sheen === 'Satin' && $sealer_level === 'Standard Duty' ) {
        $code = 'satin_standard';
    }

    if ( $code === '' ) {
        return ['error' => 'Case 10 error: No matching clause found for the given selections.'];
    }

    $clause_key = $code;
    $clause     = 'case_10:' . $clause_key;

    $items = [];

    $debug = [
        'sqft'         => $sqft,
        'sqm'          => $sqm,
        'system_label' => $system_label,
        'stone'        => $stone_label,
        'sealer_level' => $sealer_level,
        'sheen'        => $sheen,
        'clause_code'  => $code,
        'lines'        => [],
    ];

    // -----------------------------------------------------
    // Fibermesh
    // -----------------------------------------------------
    $mesh_product = ad_materials_calc_get_product_by_sku( '5200433500619' );

    if ( ! $mesh_product ) {
        return ['error' => 'Case 10 error: Could not resolve Fibermesh SKU 5200433500619.'];
    }

    $mesh_qty = (int) ceil( $sqm / 50 );

    $debug['lines'][] = [
        'line'         => 'Fibermesh',
        'rule'         => 'Case 10 always adds fibermesh: ceil(sqm / 50)',
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
    $epoxy_primer_config = ad_materials_calc_get_configured_coverage_values( 'case_10', $clause_key, $system_label, 'epoxy_primer_pair', 'Case 10 error:' );

    if ( ! empty( $epoxy_primer_config['error'] ) ) {
        return ['error' => $epoxy_primer_config['error']];
    }

    $epoxy_primer_components = $epoxy_primer_config['values'];
    $epoxy_primer_kg         = round( $sqm * array_sum( $epoxy_primer_components ), 2 );

    $epoxy_pair_bucket_prices = [
        20 => ad_materials_calc_get_product_price_by_sku( '5200433500534' ) + ad_materials_calc_get_product_price_by_sku( '5200433501692' ),
        10 => ad_materials_calc_get_product_price_by_sku( '5200433501795' ) + ad_materials_calc_get_product_price_by_sku( '5200433501796' ),
    ];

    $epoxy_combo = ad_materials_calc_get_best_2bucket_combo( $epoxy_primer_kg, $epoxy_pair_bucket_prices );

    $debug['lines'][] = [
        'line'               => 'Epoxy Primer pair',
        'coverage_kg_per_m2' => $epoxy_primer_components,
        'sqm'                => $sqm,
        'required_kg'        => $epoxy_primer_kg,
        'bucket_prices'      => $epoxy_pair_bucket_prices,
        'combo_result'       => $epoxy_combo,
        'sku_map'            => [
            '20kg_primer'    => '5200433500534',
            '20kg_activator' => '5200433501692',
            '10kg_primer'    => '5200433501795',
            '10kg_activator' => '5200433501796',
        ],
    ];

    $epoxy_20_qty = ad_materials_calc_get_combo_bucket_count( $epoxy_combo, 20 );
    $epoxy_10_qty = ad_materials_calc_get_combo_bucket_count( $epoxy_combo, 10 );

    if ( $epoxy_20_qty > 0 ) {
        $epoxy_20_primer    = ad_materials_calc_get_product_by_sku( '5200433500534' );
        $epoxy_20_activator = ad_materials_calc_get_product_by_sku( '5200433501692' );

        if ( ! $epoxy_20_primer || ! $epoxy_20_activator ) {
            return ['error' => 'Case 10 error: Could not resolve 20kg epoxy primer pair.'];
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $epoxy_20_primer, $epoxy_20_qty );
        $items[] = ad_materials_calc_build_line_item_from_product( $epoxy_20_activator, $epoxy_20_qty );
    }

    if ( $epoxy_10_qty > 0 ) {
        $epoxy_10_primer    = ad_materials_calc_get_product_by_sku( '5200433501795' );
        $epoxy_10_activator = ad_materials_calc_get_product_by_sku( '5200433501796' );

        if ( ! $epoxy_10_primer || ! $epoxy_10_activator ) {
            return ['error' => 'Case 10 error: Could not resolve 10kg epoxy primer pair.'];
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $epoxy_10_primer, $epoxy_10_qty );
        $items[] = ad_materials_calc_build_line_item_from_product( $epoxy_10_activator, $epoxy_10_qty );
    }

    // -----------------------------------------------------
    // Monolith Stone
    // -----------------------------------------------------
    $stone_config = ad_materials_calc_get_configured_coverage_values( 'case_10', $clause_key, $system_label, 'monolith_stone', 'Case 10 error:' );

    if ( ! empty( $stone_config['error'] ) ) {
        return ['error' => $stone_config['error']];
    }

    $stone_components = $stone_config['values'];
    $stone_kg         = round( $sqm * array_sum( $stone_components ), 2 );
    $stone_package_kg = 22.68;
    $stone_qty        = (int) ceil( $stone_kg / $stone_package_kg );
    $stone_product    = ad_materials_calc_find_monolith_stone_product_by_label( $stone_label );

    if ( ! $stone_product ) {
        return ['error' => 'Case 10 error: Could not resolve the selected Monolith stone variation "' . $stone_label . '".'];
    }

    $debug['lines'][] = [
        'line'               => 'Monolith Stone',
        'stone_label'        => $stone_label,
        'coverage_kg_per_m2' => $stone_components,
        'sqm'                => $sqm,
        'required_kg'        => $stone_kg,
        'package_size_kg'    => $stone_package_kg,
        'package_qty'        => $stone_qty,
        'variation_sku'      => $stone_product->get_sku(),
        'unit_price'         => ad_materials_calc_get_calculator_product_unit_price( $stone_product ),
    ];

    if ( $stone_qty > 0 ) {
        $items[] = ad_materials_calc_build_line_item_from_product( $stone_product, $stone_qty );
    }

    // -----------------------------------------------------
    // Monolith Resin
    // -----------------------------------------------------
    $resin_config = ad_materials_calc_get_configured_coverage_values( 'case_10', $clause_key, $system_label, 'monolith_resin_ratio', 'Case 10 error:' );

    if ( ! empty( $resin_config['error'] ) ) {
        return ['error' => $resin_config['error']];
    }

    $stone_resin_ratio = array_sum( $resin_config['values'] );

    if ( $stone_resin_ratio <= 0 ) {
        return ['error' => 'Case 10 error: Stone : resin ratio must be greater than zero.'];
    }

    $resin_product = ad_materials_calc_get_product_by_sku( '5200433501773' );

    if ( ! $resin_product ) {
        return ['error' => 'Case 10 error: Could not resolve Monolith Resin SKU 5200433501773.'];
    }

    $resin_kg  = round( $stone_kg / $stone_resin_ratio, 2 );
    $resin_qty = (int) ceil( $resin_kg );

    $debug['lines'][] = [
        'line'              => 'Monolith Resin',
        'stone_kg'          => $stone_kg,
        'stone_resin_ratio' => $stone_resin_ratio,
        'required_kg'       => $resin_kg,
        'package_size_kg'   => 1,
        'package_qty'       => $resin_qty,
        'sku'               => '5200433501773',
        'unit_price'        => ad_materials_calc_get_product_price_by_sku( '5200433501773' ),
    ];

    if ( $resin_qty > 0 ) {
        $items[] = ad_materials_calc_build_line_item_from_product( $resin_product, $resin_qty );
    }

    // -----------------------------------------------------
    // FLOWTECH
    // -----------------------------------------------------
    $flowtech_config = ad_materials_calc_get_configured_coverage_values( 'case_10', $clause_key, $system_label, 'monolith_flowtech_ratio', 'Case 10 error:' );

    if ( ! empty( $flowtech_config['error'] ) ) {
        return ['error' => $flowtech_config['error']];
    }

    $flowtech_ratio   = array_sum( $flowtech_config['values'] );
    $flowtech_product = ad_materials_calc_get_product_by_sku( '5200433501810' );

    if ( ! $flowtech_product ) {
        return ['error' => 'Case 10 error: Could not resolve FLOWTECH SKU 5200433501810.'];
    }

    $flowtech_kg  = round( $resin_kg * $flowtech_ratio, 2 );
    $flowtech_qty = (int) ceil( $flowtech_kg );

    $debug['lines'][] = [
        'line'            => 'FLOWTECH',
        'resin_kg'        => $resin_kg,
        'flowtech_ratio'  => $flowtech_ratio,
        'required_kg'     => $flowtech_kg,
        'package_size_kg' => 1,
        'package_qty'     => $flowtech_qty,
        'sku'             => '5200433501810',
        'unit_price'      => ad_materials_calc_get_product_price_by_sku( '5200433501810' ),
    ];

    if ( $flowtech_qty > 0 ) {
        $items[] = ad_materials_calc_build_line_item_from_product( $flowtech_product, $flowtech_qty );
    }

    // -----------------------------------------------------
    // Sealer(s)
    // -----------------------------------------------------
    $satin_components = [];
    $matte_components = [];

    if ( in_array( $code, ['matte_max', 'matte_standard', 'satin_max', 'satin_standard'], true ) ) {
        $satin_config = ad_materials_calc_get_configured_coverage_values( 'case_10', $clause_key, $system_label, 'sealer_satin', 'Case 10 error:' );

        if ( ! empty( $satin_config['error'] ) ) {
            return ['error' => $satin_config['error']];
        }

        $satin_components = $satin_config['values'];
    }

    if ( in_array( $code, ['matte_max', 'matte_standard'], true ) ) {
        $matte_config = ad_materials_calc_get_configured_coverage_values( 'case_10', $clause_key, $system_label, 'sealer_matte', 'Case 10 error:' );

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
                return ['error' => 'Case 10 error: Could not resolve 5kg matte sealer pair.'];
            }

            $items[] = ad_materials_calc_build_line_item_from_product( $matte_5kg_sealer_product, $matte_5kg_qty );
            $items[] = ad_materials_calc_build_line_item_from_product( $matte_5kg_activator, $matte_5kg_qty );
        }

        if ( $matte_1kg_qty > 0 ) {
            $matte_1kg_sealer_product = ad_materials_calc_get_product_by_sku( '5200433501785' );
            $matte_1kg_activator      = ad_materials_calc_get_product_by_sku( '5200433501786' );

            if ( ! $matte_1kg_sealer_product || ! $matte_1kg_activator ) {
                return ['error' => 'Case 10 error: Could not resolve 1kg matte sealer pair.'];
            }

            $items[] = ad_materials_calc_build_line_item_from_product( $matte_1kg_sealer_product, $matte_1kg_qty );
            $items[] = ad_materials_calc_build_line_item_from_product( $matte_1kg_activator, $matte_1kg_qty );
        }
    }

    // -----------------------------------------------------
    // Silica sand (notice only)
    // -----------------------------------------------------
    $silica_config = ad_materials_calc_get_configured_coverage_values( 'case_10', $clause_key, $system_label, 'monolith_silica', 'Case 10 error:' );

    if ( ! empty( $silica_config['error'] ) ) {
        return ['error' => $silica_config['error']];
    }

    $silica_components = $silica_config['values'];
    $silica_kg         = round( $sqm * array_sum( $silica_components ), 2 );
    $silica_package_kg = 22.68;
    $silica_packages   = (int) ceil( $silica_kg / $silica_package_kg );

    $debug['lines'][] = [
        'line'               => 'Silica sand notice only',
        'coverage_kg_per_m2' => $silica_components,
        'sqm'                => $sqm,
        'required_kg'        => $silica_kg,
        'package_size_kg'    => $silica_package_kg,
        'packages_needed'    => $silica_packages,
    ];

    $notice = ad_materials_calc_get_silica_notice_html( $silica_packages, $silica_package_kg );

    return [
        'clause' => $clause,
        'sqm'    => $sqm,
        'items'  => $items,
        'notice' => $notice,
        'debug'  => $debug,
    ];
}