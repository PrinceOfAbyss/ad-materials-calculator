<?php
/**
 * Materials Calculator - Case 2
 * Wet Area Walls
 *
 * Notes:
 * - Handles Case 2 output rendering
 * - Validates the Case 2 form answers
 * - Builds the required material line items for Wet Area Walls
 * - Returns the clause/debug data and calculated products for this case
 */

/**
 * Case 2 - Output
 */
function ad_materials_calc_case_2_output( array $answers ) {

    return ad_materials_calc_render_calculation_result( ad_materials_calc_get_calculation_from_answers( $answers ) );
}

/**
 * Case 2 Build line items
 */
function ad_materials_calc_case_2_line_items( array $answers ) {

    $sqft = isset( $answers['total_area_sqft'] ) ? (float) $answers['total_area_sqft'] : 0;

    if ( $sqft <= 0 ) {
        return ['error' => 'Case 2 error: Total surface area is missing or invalid.'];
    }

    $sqm = round( $sqft / 10.76 * 1.1, 1 );

    $sheen            = isset( $answers['sheen_wet_area'] ) ? (string) $answers['sheen_wet_area'] : '';
    $usage            = isset( $answers['usage_level_wet_area'] ) ? (string) $answers['usage_level_wet_area'] : '';
    $system_label     = isset( $answers['terrazzo_system'] ) ? (string) $answers['terrazzo_system'] : '';
    $pigment_label    = isset( $answers['pigment_color'] ) ? (string) $answers['pigment_color'] : '';
    $finish_color     = isset( $answers['finish_color'] ) ? (string) $answers['finish_color'] : '';
    $existing_tile    = isset( $answers['existing_tile_wet_area'] ) ? (string) $answers['existing_tile_wet_area'] : '';
    $tile_joint_label = isset( $answers['tile_joint_condition_wet_area'] ) ? (string) $answers['tile_joint_condition_wet_area'] : '';

    if ( $sheen !== 'Matte Dual-Layer' && $sheen !== 'Matte Single-Layer' && $sheen !== 'Satin' ) {
        return ['error' => 'Case 2 error: Unknown "Sheen" value: ' . $sheen];
    }

    if ( $usage !== 'Minimal Duty' && $usage !== 'Standard Duty' && $usage !== 'Max Duty' ) {
        return ['error' => 'Case 2 error: Unknown "Usage Level" value: ' . $usage];
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
        return ['error' => 'Case 2 error: No matching clause found for the given selections.'];
    }

    $clause_key = $code;
    $clause     = 'case_2:' . $clause_key;

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
        return ['error' => 'Case 2 error: Unknown Tile Joint Condition value: ' . $tile_joint_label];
    }

    $items = [];

    $debug = [
        'sqft'              => $sqft,
        'sqm'               => $sqm,
        'system_label'      => $system_label,
        'pigment'           => $pigment_label,
        'finish_color'      => $finish_color,
        'existing_tile'     => $existing_tile,
        'tile_joint_label'  => $tile_joint_label,
        'tile_joint_factor' => $tile_joint_factor,
        'clause_code'       => $code,
        'lines'             => [],
    ];

    $joint_filler_kg = ad_materials_calc_get_joint_filler_required_kg( $existing_tile, $sqm, $tile_joint_factor );

    $debug['lines'][] = [
        'line'                     => 'Joint Filler requirement',
        'existing_tile'            => $existing_tile,
        'tile_joint_label'         => $tile_joint_label,
        'tile_joint_factor'        => $tile_joint_factor,
        'joint_filler_required_kg' => $joint_filler_kg,
    ];

    // -----------------------------------------------------
    // Fibermesh
    // -----------------------------------------------------
    $mesh_product = ad_materials_calc_get_product_by_sku( '5200433500619' );

    if ( ! $mesh_product ) {
        return ['error' => 'Case 2 error: Could not resolve Fibermesh SKU 5200433500619.'];
    }

    $mesh_qty = (int) ceil( $sqm / 50 );

    $debug['lines'][] = [
        'line'         => 'Fibermesh',
        'rule'         => 'Case 2 always adds fibermesh: ceil(sqm / 50)',
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
    $primer_config = ad_materials_calc_get_configured_coverage_values( 'case_2', $clause_key, $system_label, 'primer', 'Case 2 error:' );

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
        'medium_large' => 0,
        'medium_small' => 0,
        'fine_large'   => 0,
        'fine_small'   => 0,
        'micro_large'  => 0,
        'micro_small'  => 0,
    ];

    if ( $system_label === 'Terrazzo Granitech Medium' ) {
        $medium_config = ad_materials_calc_get_configured_coverage_values( 'case_2', $clause_key, $system_label, 'system_medium', 'Case 2 error:' );

        if ( ! empty( $medium_config['error'] ) ) {
            return ['error' => $medium_config['error']];
        }

        $medium_components = $medium_config['values'];

        $medium_kg = round( $sqm * array_sum( $medium_components ), 2 );

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
                    return ['error' => 'Case 2 error: Could not resolve Medium 19kg SKU 5200433500015.'];
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

        if ( $joint_filler_kg > 0 ) {
            $fine_bucket_prices = [
                10 => ad_materials_calc_get_product_price_by_sku( '5200433500008' ),
                1  => ad_materials_calc_get_product_price_by_sku( '5200433501815' ),
            ];

            $joint_filler_combo = ad_materials_calc_get_best_2bucket_combo( $joint_filler_kg, $fine_bucket_prices );

            $debug['lines'][] = [
                'line'               => 'Joint Filler - Fine',
                'coverage_kg_per_m2' => [$tile_joint_factor],
                'sqm'                => $sqm,
                'required_kg'        => $joint_filler_kg,
                'bucket_prices'      => $fine_bucket_prices,
                'combo_result'       => $joint_filler_combo,
                'sku_map'            => [
                    10 => '5200433500008',
                    1  => '5200433501815',
                ],
            ];

            $items = array_merge(
                $items,
                ad_materials_calc_expand_combo_to_line_items(
                    $joint_filler_combo,
                    [
                        10 => '5200433500008',
                        1  => '5200433501815',
                    ]
                )
            );
        }
    } elseif ( $system_label === 'Terrazzo Granitech Fine' ) {
        $medium_config = ad_materials_calc_get_configured_coverage_values( 'case_2', $clause_key, $system_label, 'system_medium', 'Case 2 error:' );

        if ( ! empty( $medium_config['error'] ) ) {
            return ['error' => $medium_config['error']];
        }

        $medium_components = $medium_config['values'];

        $medium_kg = round( $sqm * array_sum( $medium_components ), 2 );

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
                    return ['error' => 'Case 2 error: Could not resolve Medium 19kg SKU 5200433500015.'];
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

        $fine_config = ad_materials_calc_get_configured_coverage_values( 'case_2', $clause_key, $system_label, 'system_fine_top', 'Case 2 error:' );

        if ( ! empty( $fine_config['error'] ) ) {
            return ['error' => $fine_config['error']];
        }

        $fine_application_components = $fine_config['values'];
        $fine_application_kg         = round( $sqm * array_sum( $fine_application_components ), 2 );
        $fine_purchase_kg            = round( $fine_application_kg + $joint_filler_kg, 2 );
        $fine_large_project_split    = ( $sqft >= 250 );

        $fine_bucket_prices = [
            10 => ad_materials_calc_get_product_price_by_sku( '5200433500008' ),
            1  => ad_materials_calc_get_product_price_by_sku( '5200433501815' ),
        ];

        $fine_application_combo  = [];
        $fine_purchase_combo     = [];
        $joint_filler_combo      = [];
        $fine_large_qty          = 0;
        $fine_small_qty          = 0;
        $fine_purchase_large_qty = 0;
        $fine_purchase_small_qty = 0;

        if ( $fine_large_project_split ) {
            $fine_large_qty          = (int) ceil( $fine_application_kg / 10 );
            $fine_purchase_large_qty = $fine_large_qty;

            if ( $joint_filler_kg > 0 ) {
                $joint_filler_combo = ad_materials_calc_get_best_2bucket_combo( $joint_filler_kg, $fine_bucket_prices );

                $fine_purchase_large_qty += ad_materials_calc_get_combo_bucket_count( $joint_filler_combo, 10 );
                $fine_purchase_small_qty = ad_materials_calc_get_combo_bucket_count( $joint_filler_combo, 1 );
            }
        } else {
            $fine_application_combo = ad_materials_calc_get_best_2bucket_combo( $fine_application_kg, $fine_bucket_prices );
            $fine_purchase_combo    = ad_materials_calc_get_best_2bucket_combo( $fine_purchase_kg, $fine_bucket_prices );

            $fine_large_qty          = ad_materials_calc_get_combo_bucket_count( $fine_application_combo, 10 );
            $fine_small_qty          = ad_materials_calc_get_combo_bucket_count( $fine_application_combo, 1 );
            $fine_purchase_large_qty = ad_materials_calc_get_combo_bucket_count( $fine_purchase_combo, 10 );
            $fine_purchase_small_qty = ad_materials_calc_get_combo_bucket_count( $fine_purchase_combo, 1 );
        }

        $debug['lines'][] = [
            'line'               => 'System - Fine application top layer',
            'coverage_kg_per_m2' => $fine_application_components,
            'sqm'                => $sqm,
            'required_kg'        => $fine_application_kg,
            'bucket_prices'      => $fine_large_project_split ? [
                10 => $fine_bucket_prices[10],
            ] : $fine_bucket_prices,
            'combo_result'       => $fine_application_combo,
            'resolved_qty'       => [
                10 => $fine_large_qty,
                1  => $fine_small_qty,
            ],
            'sku_map'            => [
                10 => '5200433500008',
                1  => '5200433501815',
            ],
        ];

        $debug['lines'][] = [
            'line'            => 'System - Fine purchased buckets',
            'application_kg'  => $fine_application_kg,
            'joint_filler_kg' => $joint_filler_kg,
            'required_kg'     => $fine_purchase_kg,
            'bucket_prices'   => $fine_bucket_prices,
            'combo_result'    => $fine_large_project_split ? $joint_filler_combo : $fine_purchase_combo,
            'resolved_qty'    => [
                10 => $fine_purchase_large_qty,
                1  => $fine_purchase_small_qty,
            ],
            'sku_map'         => [
                10 => '5200433500008',
                1  => '5200433501815',
            ],
        ];

        if ( $fine_large_project_split ) {
            $fine_product = ad_materials_calc_get_product_by_sku( '5200433500008' );

            if ( ! $fine_product ) {
                return ['error' => 'Case 2 error: Could not resolve Fine 10kg SKU 5200433500008.'];
            }

            if ( $fine_large_qty > 0 ) {
                $items[] = ad_materials_calc_build_line_item_from_product( $fine_product, $fine_large_qty );
            }

            if ( $joint_filler_kg > 0 ) {
                $debug['lines'][] = [
                    'line'               => 'Joint Filler - Fine',
                    'coverage_kg_per_m2' => [$tile_joint_factor],
                    'sqm'                => $sqm,
                    'required_kg'        => $joint_filler_kg,
                    'bucket_prices'      => $fine_bucket_prices,
                    'combo_result'       => $joint_filler_combo,
                    'sku_map'            => [
                        10 => '5200433500008',
                        1  => '5200433501815',
                    ],
                ];

                $items = array_merge(
                    $items,
                    ad_materials_calc_expand_combo_to_line_items(
                        $joint_filler_combo,
                        [
                            10 => '5200433500008',
                            1  => '5200433501815',
                        ]
                    )
                );
            }
        } else {
            $items = array_merge(
                $items,
                ad_materials_calc_expand_combo_to_line_items(
                    $fine_purchase_combo,
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
        $micro_config = ad_materials_calc_get_configured_coverage_values( 'case_2', $clause_key, $system_label, 'system_micro', 'Case 2 error:' );

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
                        return ['error' => 'Case 2 error: Could not resolve Microterrazzo 22kg SKU 5200433500022.'];
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
                return ['error' => 'Case 2 error: Could not resolve Microterrazzo Zambia SKU 5200433501770.'];
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
                return ['error' => 'Case 2 error: Could not resolve Microterrazzo Roma SKU 5200433501769.'];
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
            return ['error' => 'Case 2 error: Unknown / missing Finish Color for Microterrazzo.'];
        }

        if ( $joint_filler_kg > 0 ) {
            $fine_bucket_prices = [
                10 => ad_materials_calc_get_product_price_by_sku( '5200433500008' ),
                1  => ad_materials_calc_get_product_price_by_sku( '5200433501815' ),
            ];

            $joint_filler_combo = ad_materials_calc_get_best_2bucket_combo( $joint_filler_kg, $fine_bucket_prices );

            $debug['lines'][] = [
                'line'               => 'Joint Filler - Fine',
                'coverage_kg_per_m2' => [$tile_joint_factor],
                'sqm'                => $sqm,
                'required_kg'        => $joint_filler_kg,
                'bucket_prices'      => $fine_bucket_prices,
                'combo_result'       => $joint_filler_combo,
                'sku_map'            => [
                    10 => '5200433500008',
                    1  => '5200433501815',
                ],
            ];

            $items = array_merge(
                $items,
                ad_materials_calc_expand_combo_to_line_items(
                    $joint_filler_combo,
                    [
                        10 => '5200433500008',
                        1  => '5200433501815',
                    ]
                )
            );
        }
    } else {
        return ['error' => 'Case 2 error: Unknown System value: ' . $system_label];
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
                return ['error' => 'Case 2 error: Could not resolve the selected ' . ucfirst( $pigment_family ) . ' pigment variation "' . $pigment_label . '".'];
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
    $satin_components = [];
    $matte_components = [];

    if ( in_array( $code, ['matte_dual_max', 'matte_dual_standard', 'satin_max', 'satin_standard', 'satin_minimal'], true ) ) {
        $satin_config = ad_materials_calc_get_configured_coverage_values( 'case_2', $clause_key, $system_label, 'sealer_satin', 'Case 2 error:' );

        if ( ! empty( $satin_config['error'] ) ) {
            return ['error' => $satin_config['error']];
        }

        $satin_components = $satin_config['values'];
    }

    if ( in_array( $code, ['matte_dual_max', 'matte_dual_standard', 'matte_single_standard'], true ) ) {
        $matte_config = ad_materials_calc_get_configured_coverage_values( 'case_2', $clause_key, $system_label, 'sealer_matte', 'Case 2 error:' );

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
                return ['error' => 'Case 2 error: Could not resolve 5kg matte sealer pair.'];
            }

            $items[] = ad_materials_calc_build_line_item_from_product( $matte_5kg_sealer_product, $matte_5kg_qty );
            $items[] = ad_materials_calc_build_line_item_from_product( $matte_5kg_activator, $matte_5kg_qty );
        }

        if ( $matte_1kg_qty > 0 ) {
            $matte_1kg_sealer_product = ad_materials_calc_get_product_by_sku( '5200433501785' );
            $matte_1kg_activator      = ad_materials_calc_get_product_by_sku( '5200433501786' );

            if ( ! $matte_1kg_sealer_product || ! $matte_1kg_activator ) {
                return ['error' => 'Case 2 error: Could not resolve 1kg matte sealer pair.'];
            }

            $items[] = ad_materials_calc_build_line_item_from_product( $matte_1kg_sealer_product, $matte_1kg_qty );
            $items[] = ad_materials_calc_build_line_item_from_product( $matte_1kg_activator, $matte_1kg_qty );
        }
    }

    return [
        'clause'            => $clause,
        'sqm'               => $sqm,
        'items'             => $items,
        'debug'             => $debug,
        'joint_filler_kg'   => $joint_filler_kg,
        'joint_filler_note' => ad_materials_calc_build_joint_filler_note( $joint_filler_kg ),
    ];
}