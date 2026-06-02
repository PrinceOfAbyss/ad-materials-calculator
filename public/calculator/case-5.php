<?php
/**
 * Materials Calculator - Case 5
 * Pool Interior
 *
 * Notes:
 * - Handles Case 5 output rendering
 * - Validates the Case 5 form answers
 * - Builds the required material line items for Pool Interior
 * - Returns the clause/debug data and calculated products for this case
 */

/**
 * Case 5 - Output
 */
function ad_materials_calc_case_5_output( array $answers ) {

    return ad_materials_calc_render_calculation_result( ad_materials_calc_get_calculation_from_answers( $answers ) );
}

/**
 * Case 5 Build line items
 */
function ad_materials_calc_case_5_line_items( array $answers ) {

    $sqft = isset( $answers['total_area_sqft'] ) ? (float) $answers['total_area_sqft'] : 0;

    if ( $sqft <= 0 ) {
        return ['error' => 'Case 5 error: Total surface area is missing or invalid.'];
    }

    $sqm = round( $sqft / 10.76 * 1.1, 1 );

    $system_label  = isset( $answers['terrazzo_system'] ) ? (string) $answers['terrazzo_system'] : '';
    $pigment_label = isset( $answers['pigment_color'] ) ? (string) $answers['pigment_color'] : '';
    $finish_color  = isset( $answers['finish_color'] ) ? (string) $answers['finish_color'] : '';

    $clause_key = 'pool_interior_fixed';
    $clause     = 'case_5:' . $clause_key;

    $items = [];

    $debug = [
        'sqft'         => $sqft,
        'sqm'          => $sqm,
        'system_label' => $system_label,
        'pigment'      => $pigment_label,
        'finish_color' => $finish_color,
        'lines'        => [],
    ];

    // -----------------------------------------------------
    // Fibermesh
    // -----------------------------------------------------
    $mesh_product = ad_materials_calc_get_product_by_sku( '5200433500619' );

    if ( ! $mesh_product ) {
        return ['error' => 'Case 5 error: Could not resolve Fibermesh SKU 5200433500619.'];
    }

    $mesh_qty = (int) ceil( $sqm / 50 );

    $debug['lines'][] = [
        'line'         => 'Fibermesh',
        'rule'         => 'Case 5 always adds fibermesh: ceil(sqm / 50)',
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
    $primer_config = ad_materials_calc_get_configured_coverage_values( 'case_5', $clause_key, $system_label, 'epoxy_primer_pair', 'Case 5 error:' );

    if ( ! empty( $primer_config['error'] ) ) {
        return ['error' => $primer_config['error']];
    }

    $primer_components = $primer_config['values'];
    $primer_kg         = round( $sqm * array_sum( $primer_components ), 2 );

    $primer_bucket_prices = [
        20 => ad_materials_calc_get_product_price_by_sku( '5200433500534' ) + ad_materials_calc_get_product_price_by_sku( '5200433501692' ),
        10 => ad_materials_calc_get_product_price_by_sku( '5200433501795' ) + ad_materials_calc_get_product_price_by_sku( '5200433501796' ),
    ];

    $primer_combo = ad_materials_calc_get_best_2bucket_combo( $primer_kg, $primer_bucket_prices );

    $debug['lines'][] = [
        'line'               => 'Epoxy Primer pair',
        'coverage_kg_per_m2' => $primer_components,
        'sqm'                => $sqm,
        'required_kg'        => $primer_kg,
        'bucket_prices'      => $primer_bucket_prices,
        'combo_result'       => $primer_combo,
        'sku_map_a'          => [
            20 => '5200433500534',
            10 => '5200433501795',
        ],
        'sku_map_b'          => [
            20 => '5200433501692',
            10 => '5200433501796',
        ],
    ];

    foreach ( $primer_combo['bucket_counts'] as $bucket_size => $count ) {
        $count       = (int) $count;
        $bucket_size = (int) $bucket_size;

        if ( $count <= 0 ) {
            continue;
        }

        if ( $bucket_size === 20 ) {
            $product_a = ad_materials_calc_get_product_by_sku( '5200433500534' );
            $product_b = ad_materials_calc_get_product_by_sku( '5200433501692' );
        } else {
            $product_a = ad_materials_calc_get_product_by_sku( '5200433501795' );
            $product_b = ad_materials_calc_get_product_by_sku( '5200433501796' );
        }

        if ( ! $product_a || ! $product_b ) {
            return ['error' => 'Case 5 error: Could not resolve Epoxy Primer pair products.'];
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $product_a, $count );
        $items[] = ad_materials_calc_build_line_item_from_product( $product_b, $count );
    }

    // -----------------------------------------------------
    // System(s)
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
        $medium_config = ad_materials_calc_get_configured_coverage_values( 'case_5', $clause_key, $system_label, 'system_medium', 'Case 5 error:' );

        if ( ! empty( $medium_config['error'] ) ) {
            return ['error' => $medium_config['error']];
        }

        $medium_components = $medium_config['values'];
        $medium_kg         = round( $sqm * array_sum( $medium_components ), 2 );

        $medium_large_project_split = ( $sqft >= 250 );
        $medium_bucket_prices       = [
            19 => ad_materials_calc_get_product_price_by_sku( '5200433500015' ),
            1  => ad_materials_calc_get_product_price_by_sku( '5200433501814' ),
        ];
        $medium_combo     = [];
        $medium_large_qty = 0;
        $medium_small_qty = 0;

        if ( $medium_large_project_split ) {
            $medium_large_qty = (int) ceil( $medium_kg / 19 );
        } else {
            $medium_combo     = ad_materials_calc_get_best_2bucket_combo( $medium_kg, $medium_bucket_prices );
            $medium_large_qty = ad_materials_calc_get_combo_bucket_count( $medium_combo, 19 );
            $medium_small_qty = ad_materials_calc_get_combo_bucket_count( $medium_combo, 1 );
        }

        $debug['lines'][] = [
            'line'               => 'System - Medium',
            'coverage_kg_per_m2' => $medium_components,
            'sqm'                => $sqm,
            'required_kg'        => $medium_kg,
            'bucket_prices'      => $medium_large_project_split ? [
                19 => $medium_bucket_prices[19],
            ] : $medium_bucket_prices,
            'combo_result'       => $medium_combo,
            'resolved_qty'       => [
                19 => $medium_large_qty,
                1  => $medium_small_qty,
            ],
            'sku_map'            => [
                19 => '5200433500015',
                1  => '5200433501814',
            ],
        ];

        if ( $medium_large_project_split ) {
            if ( $medium_large_qty > 0 ) {
                $medium_product = ad_materials_calc_get_product_by_sku( '5200433500015' );

                if ( ! $medium_product ) {
                    return ['error' => 'Case 5 error: Could not resolve Medium 19kg SKU 5200433500015.'];
                }

                $items[] = ad_materials_calc_build_line_item_from_product( $medium_product, $medium_large_qty );
            }
        } else {
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
        }

        $system_counters['medium_large'] = $medium_large_qty;
        $system_counters['medium_small'] = $medium_small_qty;
    } elseif ( $system_label === 'Terrazzo Granitech Fine' ) {
        $medium_config = ad_materials_calc_get_configured_coverage_values( 'case_5', $clause_key, $system_label, 'system_medium', 'Case 5 error:' );

        if ( ! empty( $medium_config['error'] ) ) {
            return ['error' => $medium_config['error']];
        }

        $medium_components = $medium_config['values'];
        $medium_kg         = round( $sqm * array_sum( $medium_components ), 2 );

        $medium_large_project_split = ( $sqft >= 250 );
        $medium_bucket_prices       = [
            19 => ad_materials_calc_get_product_price_by_sku( '5200433500015' ),
            1  => ad_materials_calc_get_product_price_by_sku( '5200433501814' ),
        ];
        $medium_combo     = [];
        $medium_large_qty = 0;
        $medium_small_qty = 0;

        if ( $medium_large_project_split ) {
            $medium_large_qty = (int) ceil( $medium_kg / 19 );
        } else {
            $medium_combo     = ad_materials_calc_get_best_2bucket_combo( $medium_kg, $medium_bucket_prices );
            $medium_large_qty = ad_materials_calc_get_combo_bucket_count( $medium_combo, 19 );
            $medium_small_qty = ad_materials_calc_get_combo_bucket_count( $medium_combo, 1 );
        }

        $debug['lines'][] = [
            'line'               => 'System - Fine case / Medium part',
            'coverage_kg_per_m2' => $medium_components,
            'sqm'                => $sqm,
            'required_kg'        => $medium_kg,
            'bucket_prices'      => $medium_large_project_split ? [
                19 => $medium_bucket_prices[19],
            ] : $medium_bucket_prices,
            'combo_result'       => $medium_combo,
            'resolved_qty'       => [
                19 => $medium_large_qty,
                1  => $medium_small_qty,
            ],
            'sku_map'            => [
                19 => '5200433500015',
                1  => '5200433501814',
            ],
        ];

        if ( $medium_large_project_split ) {
            if ( $medium_large_qty > 0 ) {
                $medium_product = ad_materials_calc_get_product_by_sku( '5200433500015' );

                if ( ! $medium_product ) {
                    return ['error' => 'Case 5 error: Could not resolve Medium 19kg SKU 5200433500015.'];
                }

                $items[] = ad_materials_calc_build_line_item_from_product( $medium_product, $medium_large_qty );
            }
        } else {
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
        }

        $system_counters['medium_large'] = $medium_large_qty;
        $system_counters['medium_small'] = $medium_small_qty;

        $fine_config = ad_materials_calc_get_configured_coverage_values( 'case_5', $clause_key, $system_label, 'system_fine_top', 'Case 5 error:' );

        if ( ! empty( $fine_config['error'] ) ) {
            return ['error' => $fine_config['error']];
        }

        $fine_components = $fine_config['values'];
        $fine_kg         = round( $sqm * array_sum( $fine_components ), 2 );

        $fine_large_project_split = ( $sqft >= 250 );
        $fine_bucket_prices       = [
            10 => ad_materials_calc_get_product_price_by_sku( '5200433500008' ),
            1  => ad_materials_calc_get_product_price_by_sku( '5200433501815' ),
        ];
        $fine_combo     = [];
        $fine_large_qty = 0;
        $fine_small_qty = 0;

        if ( $fine_large_project_split ) {
            $fine_large_qty = (int) ceil( $fine_kg / 10 );
        } else {
            $fine_combo     = ad_materials_calc_get_best_2bucket_combo( $fine_kg, $fine_bucket_prices );
            $fine_large_qty = ad_materials_calc_get_combo_bucket_count( $fine_combo, 10 );
            $fine_small_qty = ad_materials_calc_get_combo_bucket_count( $fine_combo, 1 );
        }

        $debug['lines'][] = [
            'line'               => 'System - Fine part',
            'coverage_kg_per_m2' => $fine_components,
            'sqm'                => $sqm,
            'required_kg'        => $fine_kg,
            'bucket_prices'      => $fine_large_project_split ? [
                10 => $fine_bucket_prices[10],
            ] : $fine_bucket_prices,
            'combo_result'       => $fine_combo,
            'resolved_qty'       => [
                10 => $fine_large_qty,
                1  => $fine_small_qty,
            ],
            'sku_map'            => [
                10 => '5200433500008',
                1  => '5200433501815',
            ],
        ];

        if ( $fine_large_project_split ) {
            $fine_product = ad_materials_calc_get_product_by_sku( '5200433500008' );

            if ( ! $fine_product ) {
                return ['error' => 'Case 5 error: Could not resolve Fine 10kg SKU 5200433500008.'];
            }

            if ( $fine_large_qty > 0 ) {
                $items[] = ad_materials_calc_build_line_item_from_product( $fine_product, $fine_large_qty );
            }
        } else {
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
        }

        $system_counters['fine_large'] = $fine_large_qty;
        $system_counters['fine_small'] = $fine_small_qty;
    } elseif ( $system_label === 'Terrazzo Granitech Microterrazzo' ) {
        $micro_config = ad_materials_calc_get_configured_coverage_values( 'case_5', $clause_key, $system_label, 'system_micro', 'Case 5 error:' );

        if ( ! empty( $micro_config['error'] ) ) {
            return ['error' => $micro_config['error']];
        }

        $micro_components = $micro_config['values'];
        $micro_kg         = round( $sqm * array_sum( $micro_components ), 2 );

        if ( $finish_color === '600Q' ) {
            $micro_large_project_split = ( $sqft >= 250 );
            $micro_bucket_prices       = [
                22 => ad_materials_calc_get_product_price_by_sku( '5200433500022' ),
                1  => ad_materials_calc_get_product_price_by_sku( '5200433501816' ),
            ];
            $micro_combo     = [];
            $micro_large_qty = 0;
            $micro_small_qty = 0;

            if ( $micro_large_project_split ) {
                $micro_large_qty = (int) ceil( $micro_kg / 22 );
            } else {
                $micro_combo     = ad_materials_calc_get_best_2bucket_combo( $micro_kg, $micro_bucket_prices );
                $micro_large_qty = ad_materials_calc_get_combo_bucket_count( $micro_combo, 22 );
                $micro_small_qty = ad_materials_calc_get_combo_bucket_count( $micro_combo, 1 );
            }

            $debug['lines'][] = [
                'line'               => 'System - Microterrazzo 600Q',
                'coverage_kg_per_m2' => $micro_components,
                'sqm'                => $sqm,
                'required_kg'        => $micro_kg,
                'bucket_prices'      => $micro_large_project_split ? [
                    22 => $micro_bucket_prices[22],
                ] : $micro_bucket_prices,
                'combo_result'       => $micro_combo,
                'resolved_qty'       => [
                    22 => $micro_large_qty,
                    1  => $micro_small_qty,
                ],
                'sku_map'            => [
                    22 => '5200433500022',
                    1  => '5200433501816',
                ],
            ];

            if ( $micro_large_project_split ) {
                if ( $micro_large_qty > 0 ) {
                    $micro_product = ad_materials_calc_get_product_by_sku( '5200433500022' );

                    if ( ! $micro_product ) {
                        return ['error' => 'Case 5 error: Could not resolve Microterrazzo 22kg SKU 5200433500022.'];
                    }

                    $items[] = ad_materials_calc_build_line_item_from_product( $micro_product, $micro_large_qty );
                }
            } else {
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
            }

            $system_counters['micro_large'] = $micro_large_qty;
            $system_counters['micro_small'] = $micro_small_qty;
        } elseif ( $finish_color === 'Zambia' ) {
            $zambia_product = ad_materials_calc_get_product_by_sku( '5200433501770' );

            if ( ! $zambia_product ) {
                return ['error' => 'Case 5 error: Could not resolve Microterrazzo Zambia SKU 5200433501770.'];
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
                return ['error' => 'Case 5 error: Could not resolve Microterrazzo Roma SKU 5200433501769.'];
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
            return ['error' => 'Case 5 error: Unknown / missing Finish Color for Microterrazzo.'];
        }
    } else {
        return ['error' => 'Case 5 error: Unknown System value: ' . $system_label];
    }

    // -----------------------------------------------------
    // Colorant
    // -----------------------------------------------------
    if ( ! ad_materials_calc_is_unpigmented( $pigment_label ) ) {
        $colorant_data = ad_materials_calc_get_colorant_line_data( $system_label, $system_counters );

        $debug['lines'][] = [
            'line'                  => 'Colorant',
            'pigment_label'         => $pigment_label,
            'system_counters'       => $system_counters,
            'colorant_lines'        => $colorant_data['lines'],
            'medium_small_fraction' => $colorant_data['medium_small_fraction'],
            'fine_small_fraction'   => $colorant_data['fine_small_fraction'],
            'micro_small_fraction'  => $colorant_data['micro_small_fraction'],
        ];

        foreach ( $colorant_data['lines'] as $colorant_line ) {
            $pigment_family  = isset( $colorant_line['pigment_family'] ) ? (string) $colorant_line['pigment_family'] : '';
            $colorant_qty    = isset( $colorant_line['qty'] ) ? (int) $colorant_line['qty'] : 0;
            $pigment_product = ad_materials_calc_find_pigment_product_by_family_label( $pigment_family, $pigment_label );

            if ( ! $pigment_product ) {
                return ['error' => 'Case 5 error: Could not resolve the selected ' . ucfirst( $pigment_family ) . ' pigment variation "' . $pigment_label . '".'];
            }

            $debug['lines'][] = [
                'line'          => 'Colorant - ' . ucfirst( $pigment_family ),
                'pigment_label' => $pigment_label,
                'qty'           => $colorant_qty,
                'large_count'   => isset( $colorant_line['large_count'] ) ? (int) $colorant_line['large_count'] : 0,
                'small_count'   => isset( $colorant_line['small_count'] ) ? (int) $colorant_line['small_count'] : 0,
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
    $pool_sealer_config = ad_materials_calc_get_configured_coverage_values( 'case_5', $clause_key, $system_label, 'pool_sealer_pair', 'Case 5 error:' );

    if ( ! empty( $pool_sealer_config['error'] ) ) {
        return ['error' => $pool_sealer_config['error']];
    }

    $pool_sealer_components = $pool_sealer_config['values'];
    $pool_sealer_kg         = round( $sqm * array_sum( $pool_sealer_components ), 2 );
    $pool_sealer_qty        = (int) ceil( $pool_sealer_kg / 10 );

    $debug['lines'][] = [
        'line'               => 'Pool Sealer pair',
        'coverage_kg_per_m2' => $pool_sealer_components,
        'sqm'                => $sqm,
        'required_kg'        => $pool_sealer_kg,
        'bucket_size'        => 10,
        'qty'                => $pool_sealer_qty,
        'sku_a'              => '5200433501776',
        'sku_b'              => '5200433501777',
        'unit_price_a'       => ad_materials_calc_get_product_price_by_sku( '5200433501776' ),
        'unit_price_b'       => ad_materials_calc_get_product_price_by_sku( '5200433501777' ),
    ];

    $pool_sealer_a = ad_materials_calc_get_product_by_sku( '5200433501776' );
    $pool_sealer_b = ad_materials_calc_get_product_by_sku( '5200433501777' );

    if ( ! $pool_sealer_a || ! $pool_sealer_b ) {
        return ['error' => 'Case 5 error: Could not resolve Pool Sealer pair SKUs.'];
    }

    if ( $pool_sealer_qty > 0 ) {
        $items[] = ad_materials_calc_build_line_item_from_product( $pool_sealer_a, $pool_sealer_qty );
        $items[] = ad_materials_calc_build_line_item_from_product( $pool_sealer_b, $pool_sealer_qty );
    }

    // -----------------------------------------------------
    // Silica sand (notice only)
    // -----------------------------------------------------
    $silica_kg       = round( $sqm * 1, 2 );
    $silica_packages = (int) ceil( $silica_kg / 23 );

    $debug['lines'][] = [
        'line'               => 'Silica sand notice only',
        'coverage_kg_per_m2' => 1,
        'sqm'                => $sqm,
        'required_kg'        => $silica_kg,
        'package_size_kg'    => 23,
        'packages_needed'    => $silica_packages,
    ];

    $notice = ad_materials_calc_get_silica_notice_html( $silica_packages );

    return [
        'clause' => $clause,
        'sqm'    => $sqm,
        'items'  => $items,
        'notice' => $notice,
        'debug'  => $debug,
    ];
}