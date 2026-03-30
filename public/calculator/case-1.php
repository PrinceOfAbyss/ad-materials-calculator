<?php
/**
 * Materials Calculator - Case 1
 * Interior / Exterior Walls
 *
 * Notes:
 * - Handles Case 1 output rendering
 * - Validates the Case 1 form answers
 * - Builds the required material line items for Interior / Exterior Walls
 * - Returns the clause/debug data and calculated products for this case
 */

/**
 * =========================================================
 * CASE 1 OUTPUT
 * =========================================================
 */
function ad_materials_calc_case_1_output( array $answers ) {

    $calc = ad_materials_calc_case_1_line_items( $answers );

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
        $html .= '<div style="margin-top:20px;"><strong>Case 1 calculation debug:</strong></div>';
        $html .= '<pre style="white-space:pre-wrap;margin:10px 0 0;">' . esc_html( print_r( $calc['debug'], true ) ) . '</pre>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * =========================================================
 * CASE 1 BUILD LINE ITEMS
 * =========================================================
 */
function ad_materials_calc_case_1_line_items( array $answers ) {

    $sqft = isset( $answers['total_area_sqft'] ) ? (float) $answers['total_area_sqft'] : 0;

    if ( $sqft <= 0 ) {
        return ['error' => 'Case 1 error: Total surface area is missing or invalid.'];
    }

    $sqm = round( $sqft / 10.76 * 1.1, 1 );

    $substrate     = isset( $answers['substrate_type_wall'] ) ? (string) $answers['substrate_type_wall'] : '';
    $sheen         = isset( $answers['sheen_wall'] ) ? (string) $answers['sheen_wall'] : '';
    $usage         = isset( $answers['usage_level_wall'] ) ? (string) $answers['usage_level_wall'] : '';
    $system_label  = isset( $answers['terrazzo_system'] ) ? (string) $answers['terrazzo_system'] : '';
    $pigment_label = isset( $answers['pigment_color'] ) ? (string) $answers['pigment_color'] : '';
    $finish_color  = isset( $answers['finish_color'] ) ? (string) $answers['finish_color'] : '';

    $has_fibermesh = false;

    if ( $substrate === 'Other substrates' ) {
        $has_fibermesh = true;
    } elseif ( $substrate === 'Interior drywall (Level 4 or 5) or similarly smooth/stable substrates' ) {
        $has_fibermesh = false;
    } else {
        return ['error' => 'Case 1 error: Unknown "Substrate Type" value: ' . $substrate];
    }

    if ( $sheen !== 'Matte' && $sheen !== 'Semi-Gloss' && $sheen !== 'Satin' ) {
        return ['error' => 'Case 1 error: Unknown "Sheen" value: ' . $sheen];
    }

    if ( $usage !== 'Standard Duty' && $usage !== 'Max Duty' ) {
        return ['error' => 'Case 1 error: Unknown "Usage Level" value: ' . $usage];
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
        return ['error' => 'Case 1 error: Could not determine internal clause code.'];
    }

    $clause = '';

    if ( ! $has_fibermesh ) {
        if ( $code === 'matte_standard' ) {
            $clause = 'Matte - No fibermesh - Standard Duty: Rows 71-74';
        } elseif ( $code === 'matte_max' ) {
            $clause = 'Matte - No fibermesh - Max Duty: Rows 71-73 and cells G117 + G118 in the Assuptions tab replace cell 74 in the Medium tab';
        } elseif ( $code === 'semigloss_standard' ) {
            $clause = 'Semi-Gloss - No fibermesh - Standard Duty: Rows 71-73 + G162 in the Assuptions tab replace cell 74 in the medium tab';
        } elseif ( $code === 'semigloss_max' ) {
            $clause = 'Semi-Gloss - No fibermesh - Max Duty: Rows 71-73 + G162 + G163 in the Assuptions tab replace cell 74 in the medium tab';
        } elseif ( $code === 'satin_standard' ) {
            $clause = 'Satin - No fibermesh - Standard Duty: Rows 71-73 + G113 in the Assuptions tab replace cell 74 in the medium tab';
        } elseif ( $code === 'satin_max' ) {
            $clause = 'Satin - No fibermesh - Max Duty: Rows 71-73 + G131 in the Assuptions tab replace cell 74 in the medium tab';
        }
    } else {
        if ( $code === 'matte_standard' ) {
            $clause = 'Matte - With fibermesh - Standard Duty: Rows 92-96';
        } elseif ( $code === 'matte_max' ) {
            $clause = 'Matte - With fibermesh - Max Duty: Rows 92-95 and cells G117+G118 in the Assuptions tab replace cell 76 in the medium tab';
        } elseif ( $code === 'semigloss_standard' ) {
            $clause = 'Semi-Gloss - With fibermesh - Standard Duty: Rows 92-95  + G162 in the Assuptions tab replace cell 76 in the medium tab';
        } elseif ( $code === 'semigloss_max' ) {
            $clause = 'Semi-Gloss - With fibermesh - Max Duty: Rows 92-95  + G162 + G163 in the Assuptions tab replace cell 76 in the medium tab';
        } elseif ( $code === 'satin_standard' ) {
            $clause = 'Satin - With fibermesh - Standard Duty: Rows 92-95 + G113 in the Assuptions tab replace cell 76 in the medium tab';
        } elseif ( $code === 'satin_max' ) {
            $clause = 'Satin - With fibermesh - Max Duty: Rows 92-95 + G131 in the Assuptions tab replace cell 76 in the medium tab';
        }
    }

    if ( $clause === '' ) {
        return ['error' => 'Case 1 error: No matching clause found for the given selections.'];
    }

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
    // Fibermesh (only for "With fibermesh")
    // -----------------------------------------------------
    if ( $has_fibermesh ) {
        $mesh_product = ad_materials_calc_get_product_by_sku( '5200433500619' );

        if ( ! $mesh_product ) {
            return ['error' => 'Case 1 error: Could not resolve Fibermesh SKU 5200433500619.'];
        }

        $mesh_qty = (int) ceil( $sqm / 50 );

        $debug['lines'][] = [
            'line'         => 'Fibermesh',
            'rule'         => 'With fibermesh clause => add 1 mesh roll per 50 sqm rounded up',
            'sqm'          => $sqm,
            'required_sqm' => ceil( $sqm ),
            'mesh_qty'     => $mesh_qty,
            'sku'          => '5200433500619',
            'unit_price'   => ad_materials_calc_get_product_price_by_sku( '5200433500619' ),
        ];

        if ( $mesh_qty > 0 ) {
            $items[] = ad_materials_calc_build_line_item_from_product( $mesh_product, $mesh_qty );
        }
    }

    // -----------------------------------------------------
    // Primer (all systems, all clauses)
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
        $medium_components = [2.000];
        $medium_kg         = round( $sqm * array_sum( $medium_components ), 2 );

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
        $medium_components = [2.000];
        $medium_kg         = round( $sqm * array_sum( $medium_components ), 2 );

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

        $fine_components = [0.450];
        $fine_kg       = round( $sqm * array_sum( $fine_components ), 2 );

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
                return ['error' => 'Case 1 error: Could not resolve Microterrazzo Zambia SKU 5200433501770.'];
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
                return ['error' => 'Case 1 error: Could not resolve Microterrazzo Roma SKU 5200433501769.'];
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
            return ['error' => 'Case 1 error: Unknown / missing Finish Color for Microterrazzo.'];
        }
    } else {
        return ['error' => 'Case 1 error: Unknown System value: ' . $system_label];
    }

    // -----------------------------------------------------
    // Colorant (ONLY if Pigment is chosen)
    // -----------------------------------------------------
    if ( ! ad_materials_calc_is_unpigmented( $pigment_label ) ) {
        $pigment_product = ad_materials_calc_find_pigment_product_by_label( $system_label, $pigment_label );

        if ( ! $pigment_product ) {
            return ['error' => 'Case 1 error: Could not resolve the selected pigment variation "' . $pigment_label . '".'];
        }

        $colorant_qty = 0;

        if ( $system_label === 'Terrazzo Granitech Medium' ) {
            $small_fraction = $system_counters['medium_small'] / 19;

            $colorant_qty =
            $system_counters['medium_large'] +
            (int) ceil( $small_fraction );
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

            $colorant_qty =
            $system_counters['micro_large'] +
            (int) ceil( $small_fraction );
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
    // Sealer
    // -----------------------------------------------------
    if ( $code === 'matte_standard' ) {
        $sealer_components = [0.220];
        $sealer_kg       = round( $sqm * array_sum( $sealer_components ), 2 );
        $sealer_qty      = (int) ceil( $sealer_kg / 3 );

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
            return ['error' => 'Case 1 error: Could not resolve Matte sealer / activator SKUs.'];
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $sealer_product, $sealer_qty );
        $items[] = ad_materials_calc_build_line_item_from_product( $activator_product, $sealer_qty );
    }

    if ( $code === 'matte_max' ) {
        $sealer_components = [0.220, 0.070];
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
            return ['error' => 'Case 1 error: Could not resolve Matte sealer / activator SKUs.'];
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $sealer_product, $sealer_qty );
        $items[] = ad_materials_calc_build_line_item_from_product( $activator_product, $sealer_qty );
    }

    if ( $code === 'semigloss_standard' ) {
        $sealer_components = [0.170];
        $sealer_kg       = round( $sqm * array_sum( $sealer_components ), 2 );
        $sealer_qty      = (int) ceil( $sealer_kg / 5 );

        $debug['lines'][] = [
            'line'               => 'Sealer b1 - Semi Gloss',
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
            return ['error' => 'Case 1 error: Could not resolve Semi-Gloss SKU 5200433500589.'];
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $semi_product, $sealer_qty );
    }

    if ( $code === 'semigloss_max' ) {
        $sealer_components = [0.170, 0.120];
        $sealer_kg         = round( $sqm * array_sum( $sealer_components ), 2 );
        $sealer_qty        = (int) ceil( $sealer_kg / 5 );

        $debug['lines'][] = [
            'line'               => 'Sealer b2 - Semi Gloss',
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
            return ['error' => 'Case 1 error: Could not resolve Semi-Gloss SKU 5200433500589.'];
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $semi_product, $sealer_qty );
    }

    if ( $code === 'satin_standard' ) {
        $sealer_components = [0.197];
        $sealer_kg       = round( $sqm * array_sum( $sealer_components ), 2 );

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
        $sealer_components = [0.197, 0.080];
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
