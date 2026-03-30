<?php
/**
 * Materials Calculator - Case 3
 * Interior Floor
 *
 * Notes:
 * - Handles Case 3 output rendering
 * - Validates the Case 3 form answers
 * - Builds the required material line items for Interior Floor
 * - Returns the clause/debug data and calculated products for this case
 */

/**
 * =========================================================
 * CASE 3 OUTPUT
 * =========================================================
 */
function ad_materials_calc_case_3_output( array $answers ) {

    $calc = ad_materials_calc_case_3_line_items( $answers );

    if ( isset( $calc['error'] ) ) {
        $error_output = isset( $calc['clause'] ) && $calc['clause'] !== '' ? esc_html( $calc['clause'] ) . '<br>' . esc_html( $calc['error'] ) : esc_html( $calc['error'] );

        return '<div class="materials-calc-results"><pre style="white-space:pre-wrap;margin:0;">' . $error_output . '</pre></div>';
    }

    $html = '<div class="materials-calc-results">';
    $html .= '<div style="margin-bottom:14px;">';
    $html .= '<strong>Matched clause:</strong><br>' . esc_html( $calc['clause'] );
    $html .= '</div>';
    $html .= ad_materials_calc_render_results_table( $calc['items'] );

    if ( ! empty( $calc['debug'] ) ) {
        $html .= '<div style="margin-top:20px;"><strong>Case 3 calculation debug:</strong></div>';
        $html .= '<pre style="white-space:pre-wrap;margin:10px 0 0;">' . esc_html( print_r( $calc['debug'], true ) ) . '</pre>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * =========================================================
 * CASE 3 BUILD LINE ITEMS
 * =========================================================
 */
function ad_materials_calc_case_3_line_items( array $answers ) {

    $sqft = isset( $answers['total_area_sqft'] ) ? (float) $answers['total_area_sqft'] : 0;

    if ( $sqft <= 0 ) {
        return ['error' => 'Case 3 error: Total surface area is missing or invalid.'];
    }

    $sqm = round( $sqft / 10.76 * 1.1, 1 );

    $moisture         = isset( $answers['moisture_exposure_interior_floor'] ) ? (string) $answers['moisture_exposure_interior_floor'] : '';
    $sheen            = isset( $answers['sheen_interior_floor'] ) ? (string) $answers['sheen_interior_floor'] : '';
    $usage            = isset( $answers['usage_level_interior_floor'] ) ? (string) $answers['usage_level_interior_floor'] : '';
    $system_label     = isset( $answers['terrazzo_system'] ) ? (string) $answers['terrazzo_system'] : '';
    $pigment_label    = isset( $answers['pigment_color'] ) ? (string) $answers['pigment_color'] : '';
    $finish_color     = isset( $answers['finish_color'] ) ? (string) $answers['finish_color'] : '';
    $moisture_label   = $moisture;
    $tile_joint_label = isset( $answers['tile_joint_condition_interior_floor'] ) ? (string) $answers['tile_joint_condition_interior_floor'] : '';

    if ( $moisture !== 'Wet Area' && $moisture !== 'Dry Area' ) {
        return ['error' => 'Case 3 error: Unknown "Moisture Exposure" value: ' . $moisture];
    }

    if ( $sheen !== 'Matte' && $sheen !== 'Satin' ) {
        return ['error' => 'Case 3 error: Unknown "Sheen" value: ' . $sheen];
    }

    if ( $usage !== 'Standard Duty' && $usage !== 'Max Duty' ) {
        return ['error' => 'Case 3 error: Unknown "Usage Level" value: ' . $usage];
    }

    $code = '';

    if ( $moisture === 'Wet Area' && $usage === 'Max Duty' ) {
        $code = 'wet_matte_max';
    } elseif ( $moisture === 'Wet Area' && $usage === 'Standard Duty' ) {
        $code = 'wet_matte_standard';
    } elseif ( $moisture === 'Dry Area' && $sheen === 'Matte' && $usage === 'Max Duty' ) {
        $code = 'dry_matte_max';
    } elseif ( $moisture === 'Dry Area' && $sheen === 'Matte' && $usage === 'Standard Duty' ) {
        $code = 'dry_matte_standard';
    } elseif ( $moisture === 'Dry Area' && $sheen === 'Satin' && $usage === 'Max Duty' ) {
        $code = 'dry_satin_max';
    } elseif ( $moisture === 'Dry Area' && $sheen === 'Satin' && $usage === 'Standard Duty' ) {
        $code = 'dry_satin_standard';
    }

    if ( $code === '' ) {
        return ['error' => 'Case 3 error: No matching clause found for the given selections.'];
    }

    $clause = '';

    if ( $code === 'wet_matte_max' ) {
        $clause = 'Wet Area - Matte - Dual-Layer System - (Max Duty - 2× Satin + 1× Matte): Excel reference is row 138-146';
    } elseif ( $code === 'wet_matte_standard' ) {
        $clause = 'Wet Area - Matte - Dual-Layer System - (Standard Duty - 1× Satin + 1× Matte): Excel reference is row 114-122';
    } elseif ( $code === 'dry_matte_max' ) {
        $clause = 'Dry Area - Matte - Dual-Layer System - (Max Duty - 2× Satin + 1× Matte): Excel reference is row 138-146';
    } elseif ( $code === 'dry_matte_standard' ) {
        $clause = 'Dry Area - Matte - Dual-Layer System - (Standard Duty - 1× Satin + 1× Matte): Excel reference is row 114-122';
    } elseif ( $code === 'dry_satin_max' ) {
        $clause = 'Dry Area - Satin - (Max Duty - 3× Coats): Excel reference is row 138-144';
    } elseif ( $code === 'dry_satin_standard' ) {
        $clause = 'Dry Area - Satin - (Standard Duty - 1× Coat): Excel reference is row 114-120';
    }

    $tile_joint_factor = 0;

    if ( $tile_joint_label === 'Standard Tile Joints' ) {
        $tile_joint_factor = 0.051;
    } elseif ( $tile_joint_label === 'Deep Tile Joints' ) {
        $tile_joint_factor = 0.060;
    } elseif ( $tile_joint_label === 'Mosaic / Very Uneven Surface' ) {
        $tile_joint_factor = 0.070;
    } elseif ( $tile_joint_label === 'Close joints with regular thinset' || $tile_joint_label === '' ) {
        $tile_joint_factor = 0;
    } else {
        return ['error' => 'Case 3 error: Unknown Tile Joint Condition value: ' . $tile_joint_label];
    }

    $items = [];

    $debug = [
        'sqft'              => $sqft,
        'sqm'               => $sqm,
        'system_label'      => $system_label,
        'pigment'           => $pigment_label,
        'finish_color'      => $finish_color,
        'moisture_label'    => $moisture_label,
        'tile_joint_label'  => $tile_joint_label,
        'tile_joint_factor' => $tile_joint_factor,
        'clause_code'       => $code,
        'lines'             => [],
    ];

    // -----------------------------------------------------
    // Fibermesh (all Case 3 clauses)
    // -----------------------------------------------------
    $mesh_product = ad_materials_calc_get_product_by_sku( '5200433500619' );

    if ( ! $mesh_product ) {
        return ['error' => 'Case 3 error: Could not resolve Fibermesh SKU 5200433500619.'];
    }

    $mesh_qty = (int) ceil( $sqm / 50 );

    $debug['lines'][] = [
        'line'         => 'Fibermesh',
        'rule'         => 'Case 3 always adds fibermesh: ceil(sqm / 50)',
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
    $primer_components    = [0.341];
    $primer_kg            = round( $sqm * array_sum( $primer_components ), 2 );
    $primer_bucket_prices = [
        15 => ad_materials_calc_get_product_price_by_sku( '5200433500541' ),
        3  => ad_materials_calc_get_product_price_by_sku( '5200433501811' ),
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
            3  => '5200433501811',
        ],
    ];

    $items = array_merge(
        $items,
        ad_materials_calc_expand_combo_to_line_items(
            $primer_combo,
            [
                15 => '5200433500541',
                3  => '5200433501811',
            ]
        )
    );

    // -----------------------------------------------------
    // Systems
    // -----------------------------------------------------
    $system_counters = [
        'medium_large' => 0,
        'medium_small' => 0,
        'fine_large'   => 0,
        'fine_small'   => 0,
        'micro_large'  => 0,
        'micro_small'  => 0,
    ];

    if ( $system_label === 'Terrazzo Granitech Medium' ) {
        $medium_components = in_array( $code, ['wet_matte_max', 'dry_matte_max', 'dry_satin_max'], true )
        ? [1.341, 1.040, 0.590]
        : [1.200, 1.260];

        $medium_kg = round( $sqm * array_sum( $medium_components ), 2 );

        $medium_bucket_prices = [
            19 => ad_materials_calc_get_product_price_by_sku( '5200433500015' ),
            1  => ad_materials_calc_get_product_price_by_sku( '5200433501814' ),
        ];

        $medium_combo = ad_materials_calc_get_best_2bucket_combo( $medium_kg, $medium_bucket_prices );

        $debug['lines'][] = [
            'line'               => 'System - Medium',
            'coverage_kg_per_m2' => $medium_components,
            'sqm'                => $sqm,
            'required_kg'        => $medium_kg,
            'bucket_prices'      => $medium_bucket_prices,
            'combo_result'       => $medium_combo,
            'sku_map'            => [
                19 => '5200433500015',
                1  => '5200433501814',
            ],
        ];

        $items = array_merge(
            $items,
            ad_materials_calc_expand_combo_to_line_items(
                $medium_combo,
                [
                    19 => '5200433500015',
                    1  => '5200433501814',
                ]
            )
        );

        $system_counters['medium_large'] = ad_materials_calc_get_combo_bucket_count( $medium_combo, 19 );
        $system_counters['medium_small'] = ad_materials_calc_get_combo_bucket_count( $medium_combo, 1 );
    } elseif ( $system_label === 'Terrazzo Granitech Fine' ) {
        $medium_components = in_array( $code, ['wet_matte_max', 'dry_matte_max', 'dry_satin_max'], true )
        ? [1.200, 1.260, 0.500]
        : [1.200, 1.260];

        $medium_kg = round( $sqm * array_sum( $medium_components ), 2 );

        $medium_bucket_prices = [
            19 => ad_materials_calc_get_product_price_by_sku( '5200433500015' ),
            1  => ad_materials_calc_get_product_price_by_sku( '5200433501814' ),
        ];

        $medium_combo = ad_materials_calc_get_best_2bucket_combo( $medium_kg, $medium_bucket_prices );

        $debug['lines'][] = [
            'line'               => 'System - Fine case / Medium part',
            'coverage_kg_per_m2' => $medium_components,
            'sqm'                => $sqm,
            'required_kg'        => $medium_kg,
            'bucket_prices'      => $medium_bucket_prices,
            'combo_result'       => $medium_combo,
            'sku_map'            => [
                19 => '5200433500015',
                1  => '5200433501814',
            ],
        ];

        $items = array_merge(
            $items,
            ad_materials_calc_expand_combo_to_line_items(
                $medium_combo,
                [
                    19 => '5200433500015',
                    1  => '5200433501814',
                ]
            )
        );

        $system_counters['medium_large'] = ad_materials_calc_get_combo_bucket_count( $medium_combo, 19 );
        $system_counters['medium_small'] = ad_materials_calc_get_combo_bucket_count( $medium_combo, 1 );

        $fine_components = [0.450, $tile_joint_factor];
        $fine_kg         = round( $sqm * array_sum( $fine_components ), 2 );

        $fine_bucket_prices = [
            10 => ad_materials_calc_get_product_price_by_sku( '5200433500008' ),
            1  => ad_materials_calc_get_product_price_by_sku( '5200433501815' ),
        ];

        $fine_combo = ad_materials_calc_get_best_2bucket_combo( $fine_kg, $fine_bucket_prices );

        $debug['lines'][] = [
            'line'               => 'System - Fine part',
            'coverage_kg_per_m2' => $fine_components,
            'sqm'                => $sqm,
            'required_kg'        => $fine_kg,
            'bucket_prices'      => $fine_bucket_prices,
            'combo_result'       => $fine_combo,
            'sku_map'            => [
                10 => '5200433500008',
                1  => '5200433501815',
            ],
        ];

        $items = array_merge(
            $items,
            ad_materials_calc_expand_combo_to_line_items(
                $fine_combo,
                [
                    10 => '5200433500008',
                    1  => '5200433501815',
                ]
            )
        );

        $system_counters['fine_large'] = ad_materials_calc_get_combo_bucket_count( $fine_combo, 10 );
        $system_counters['fine_small'] = ad_materials_calc_get_combo_bucket_count( $fine_combo, 1 );
    } elseif ( $system_label === 'Terrazzo Granitech Microterrazzo' ) {
        $micro_components = [2.050, 2.200];
        $micro_kg         = round( $sqm * array_sum( $micro_components ), 2 );

        if ( $finish_color === '600Q' ) {
            $micro_bucket_prices = [
                22 => ad_materials_calc_get_product_price_by_sku( '5200433500022' ),
                1  => ad_materials_calc_get_product_price_by_sku( '5200433501816' ),
            ];

            $micro_combo = ad_materials_calc_get_best_2bucket_combo( $micro_kg, $micro_bucket_prices );

            $debug['lines'][] = [
                'line'               => 'System - Microterrazzo 600Q',
                'coverage_kg_per_m2' => $micro_components,
                'sqm'                => $sqm,
                'required_kg'        => $micro_kg,
                'bucket_prices'      => $micro_bucket_prices,
                'combo_result'       => $micro_combo,
                'sku_map'            => [
                    22 => '5200433500022',
                    1  => '5200433501816',
                ],
            ];

            $items = array_merge(
                $items,
                ad_materials_calc_expand_combo_to_line_items(
                    $micro_combo,
                    [
                        22 => '5200433500022',
                        1  => '5200433501816',
                    ]
                )
            );

            $system_counters['micro_large'] = ad_materials_calc_get_combo_bucket_count( $micro_combo, 22 );
            $system_counters['micro_small'] = ad_materials_calc_get_combo_bucket_count( $micro_combo, 1 );
        } elseif ( $finish_color === 'Zambia' ) {
            $zambia_product = ad_materials_calc_get_product_by_sku( '5200433501770' );

            if ( ! $zambia_product ) {
                return ['error' => 'Case 3 error: Could not resolve Microterrazzo Zambia SKU 5200433501770.'];
            }

            $zambia_qty = (int) ceil( $micro_kg / 25 );

            $debug['lines'][] = [
                'line'               => 'System - Microterrazzo Zambia',
                'coverage_kg_per_m2' => $micro_components,
                'sqm'                => $sqm,
                'required_kg'        => $micro_kg,
                'bucket_size'        => 25,
                'unit_price'         => ad_materials_calc_get_product_price_by_sku( '5200433501770' ),
                'qty'                => $zambia_qty,
                'sku'                => '5200433501770',
            ];

            $items[]                        = ad_materials_calc_build_line_item_from_product( $zambia_product, $zambia_qty );
            $system_counters['micro_large'] = $zambia_qty;
        } elseif ( $finish_color === 'Roma' ) {
            $roma_product = ad_materials_calc_get_product_by_sku( '5200433501769' );

            if ( ! $roma_product ) {
                return ['error' => 'Case 3 error: Could not resolve Microterrazzo Roma SKU 5200433501769.'];
            }

            $roma_qty = (int) ceil( $micro_kg / 25 );

            $debug['lines'][] = [
                'line'               => 'System - Microterrazzo Roma',
                'coverage_kg_per_m2' => $micro_components,
                'sqm'                => $sqm,
                'required_kg'        => $micro_kg,
                'bucket_size'        => 25,
                'unit_price'         => ad_materials_calc_get_product_price_by_sku( '5200433501769' ),
                'qty'                => $roma_qty,
                'sku'                => '5200433501769',
            ];

            $items[]                        = ad_materials_calc_build_line_item_from_product( $roma_product, $roma_qty );
            $system_counters['micro_large'] = $roma_qty;
        } else {
            return ['error' => 'Case 3 error: Unknown / missing Finish Color for Microterrazzo.'];
        }
    } else {
        return ['error' => 'Case 3 error: Unknown System value: ' . $system_label];
    }

    // -----------------------------------------------------
    // Colorant
    // -----------------------------------------------------
    if ( ! ad_materials_calc_is_unpigmented( $pigment_label ) ) {
        $pigment_product = ad_materials_calc_find_pigment_product_by_label( $system_label, $pigment_label );

        if ( ! $pigment_product ) {
            return ['error' => 'Case 3 error: Could not resolve the selected pigment variation "' . $pigment_label . '".'];
        }

        $colorant_qty = 0;

        if ( $system_label === 'Terrazzo Granitech Medium' ) {
            $small_fraction = $system_counters['medium_small'] / 19;
            $colorant_qty   = $system_counters['medium_large'] + (int) ceil( $small_fraction );
        }

        if ( $system_label === 'Terrazzo Granitech Fine' ) {
            $small_fraction = 0;
            $small_fraction += $system_counters['medium_small'] / 19;
            $small_fraction += $system_counters['fine_small'] / 10;

            $colorant_qty =
            $system_counters['medium_large'] +
            $system_counters['fine_large'] +
            (int) ceil( $small_fraction );
        }

        if ( $system_label === 'Terrazzo Granitech Microterrazzo' ) {
            $small_fraction = $system_counters['micro_small'] / 22;
            $colorant_qty   = $system_counters['micro_large'] + (int) ceil( $small_fraction );
        }

        $debug['lines'][] = [
            'line'                  => 'Colorant',
            'pigment_label'         => $pigment_label,
            'system_counters'       => $system_counters,
            'final_qty'             => $colorant_qty,
            'variation_sku'         => $pigment_product->get_sku(),
            'unit_price'            => (float) wc_get_price_to_display( $pigment_product ),
            'medium_small_fraction' => ad_materials_calc_format_fraction_debug( $system_counters['medium_small'], 19 ),
            'fine_small_fraction'   => ad_materials_calc_format_fraction_debug( $system_counters['fine_small'], 10 ),
            'micro_small_fraction'  => ad_materials_calc_format_fraction_debug( $system_counters['micro_small'], 22 ),
        ];

        if ( $colorant_qty > 0 ) {
            $items[] = ad_materials_calc_build_line_item_from_product( $pigment_product, $colorant_qty );
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
    // Sealer coverages
    // -----------------------------------------------------
    $satin_components = [];
    $matte_components = [];

    if ( $system_label === 'Terrazzo Granitech Medium' || $system_label === 'Terrazzo Granitech Microterrazzo' ) {
        if ( $code === 'wet_matte_max' || $code === 'dry_matte_max' ) {
            $satin_components = [0.341];
            $matte_components = [0.080];
        } elseif ( $code === 'wet_matte_standard' || $code === 'dry_matte_standard' ) {
            $satin_components = [0.197];
            $matte_components = [0.080];
        } elseif ( $code === 'dry_satin_max' ) {
            $satin_components = [0.441];
        } elseif ( $code === 'dry_satin_standard' ) {
            $satin_components = [0.197];
        }
    }

    if ( $system_label === 'Terrazzo Granitech Fine' ) {
        if ( $code === 'wet_matte_max' || $code === 'dry_matte_max' ) {
            $satin_components = [0.120];
            $matte_components = [0.080];
        } elseif ( $code === 'wet_matte_standard' || $code === 'dry_matte_standard' ) {
            $satin_components = [0.060];
            $matte_components = [0.080];
        } elseif ( $code === 'dry_satin_max' ) {
            $satin_components = [0.120];
        } elseif ( $code === 'dry_satin_standard' ) {
            $satin_components = [0.060];
        }
    }

    // -----------------------------------------------------
    // Satin sealer
    // -----------------------------------------------------
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

    // -----------------------------------------------------
    // Matte sealer pair
    // -----------------------------------------------------
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
                return ['error' => 'Case 3 error: Could not resolve 5kg matte sealer pair.'];
            }

            $items[] = ad_materials_calc_build_line_item_from_product( $matte_5kg_sealer_product, $matte_5kg_qty );
            $items[] = ad_materials_calc_build_line_item_from_product( $matte_5kg_activator, $matte_5kg_qty );
        }

        if ( $matte_1kg_qty > 0 ) {
            $matte_1kg_sealer_product = ad_materials_calc_get_product_by_sku( '5200433501785' );
            $matte_1kg_activator      = ad_materials_calc_get_product_by_sku( '5200433501786' );

            if ( ! $matte_1kg_sealer_product || ! $matte_1kg_activator ) {
                return ['error' => 'Case 3 error: Could not resolve 1kg matte sealer pair.'];
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
