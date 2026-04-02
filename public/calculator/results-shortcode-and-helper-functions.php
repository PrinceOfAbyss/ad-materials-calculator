<?php
/**
 * Materials Calculator - Results shortcode and Helper functions
 *
 * Notes:
 * - Reads the user's submitted calculator answers
 * - Detects the matching calculation case
 * - Routes the calculation to the appropriate case-specific function
 * - Outputs the final results table and notices
 * - Includes shared helper functions used across all calculator cases
 */

add_shortcode( 'materials_calc_results', 'ad_materials_calc_results_shortcode' );
function ad_materials_calc_results_shortcode() {

    // Only show results after a submit redirect flag is present
    if ( empty( $_GET['mc'] ) || $_GET['mc'] !== '1' ) {
        return '';
    }

    if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->session ) {
        return '<div class="materials-calc-results">We couldn\'t load your previous answers. Please try again in a moment.</div>';
    }

    $answers = WC()->session->get( 'materials_calc_answers' );

    if ( empty( $answers ) || ! is_array( $answers ) ) {
        return '<div class="materials-calc-results">We couldn\'t retrieve your previous answers. Please fill in the calculator form and try again.</div>';
    }

    $calc   = ad_materials_calc_get_calculation_from_answers( $answers );
    $output = ad_materials_calc_render_calculation_result( $calc );

    $output = '<pre>' . print_r( $answers, true ) . '</pre>' . $output;

    if ( function_exists( 'ad_materials_calc_render_add_to_cart_block' ) ) {
        $output .= ad_materials_calc_render_add_to_cart_block( $answers, $calc );
    }

    // Clear the stored calculator answers after rendering the results
    WC()->session->set( 'materials_calc_answers', null );

    return $output;
}

function ad_materials_calc_get_calculation_from_answers( array $answers ) {

    $surface_type = isset( $answers['surface_type'] ) ? (string) $answers['surface_type'] : '';

    if ( $surface_type === '' ) {
        return [
            'error' => 'Surface Type is missing.',
        ];
    }

    $case_map = [
        'Interior / Exterior Walls'         => [
            'callback'    => 'ad_materials_calc_case_1_line_items',
            'debug_title' => 'Case 1 calculation debug:',
        ],
        'Wet Area Walls'                    => [
            'callback'    => 'ad_materials_calc_case_2_line_items',
            'debug_title' => 'Case 2 calculation debug:',
        ],
        'Interior Floor'                    => [
            'callback'    => 'ad_materials_calc_case_3_line_items',
            'debug_title' => 'Case 3 calculation debug:',
        ],
        'Exterior Floor (incl. Pool Decks)' => [
            'callback'    => 'ad_materials_calc_case_4_line_items',
            'debug_title' => 'Case 4 calculation debug:',
        ],
        'Pool Interior'                     => [
            'callback'    => 'ad_materials_calc_case_5_line_items',
            'debug_title' => 'Case 5 calculation debug:',
        ],
    ];

    if ( empty( $case_map[$surface_type] ) ) {
        return [
            'error' => 'Unsupported Surface Type: ' . $surface_type,
        ];
    }

    $case_config = $case_map[$surface_type];
    $callback    = $case_config['callback'];

    if ( ! function_exists( $callback ) ) {
        return [
            'error' => 'Calculation callback is unavailable for Surface Type: ' . $surface_type,
        ];
    }

    $calc = $callback( $answers );

    if ( ! is_array( $calc ) ) {
        return [
            'error' => 'Calculation did not return a valid result for Surface Type: ' . $surface_type,
        ];
    }

    $calc['surface_type'] = $surface_type;
    $calc['debug_title']  = $case_config['debug_title'];

    return $calc;
}

function ad_materials_calc_render_calculation_result( array $calc ) {

    if ( isset( $calc['error'] ) ) {
        $error_output = isset( $calc['clause'] ) && $calc['clause'] !== '' ? esc_html( $calc['clause'] ) . '<br>' . esc_html( $calc['error'] ) : esc_html( $calc['error'] );

        return '<div class="materials-calc-results"><pre style="white-space:pre-wrap;margin:0;">' . $error_output . '</pre></div>';
    }

    $html = '<div class="materials-calc-results">';
    $html .= '<div style="margin-bottom:14px;">';
    $html .= '<strong>Matched clause:</strong><br>' . esc_html( isset( $calc['clause'] ) ? $calc['clause'] : '' );
    $html .= '</div>';
    $html .= ad_materials_calc_render_results_table( isset( $calc['items'] ) && is_array( $calc['items'] ) ? $calc['items'] : [] );

    if ( ! empty( $calc['notice'] ) ) {
        $html .= '<div style="margin-top:16px;padding:12px;border:1px solid #ddd;border-radius:4px;background:#fafafa;">';
        $html .= wp_kses_post( $calc['notice'] );
        $html .= '</div>';
    }

    if ( ! empty( $calc['debug'] ) ) {
        $debug_title = ! empty( $calc['debug_title'] ) ? (string) $calc['debug_title'] : 'Calculation debug:';
        $html .= '<div style="margin-top:20px;"><strong>' . esc_html( $debug_title ) . '</strong></div>';
        $html .= '<pre style="white-space:pre-wrap;margin:10px 0 0;">' . esc_html( print_r( $calc['debug'], true ) ) . '</pre>';
    }

    $html .= '</div>';

    return $html;
}

function ad_materials_calc_build_cart_payload_from_calc( array $calc, array $answers ) {

    if ( empty( $calc ) || ! empty( $calc['error'] ) || empty( $calc['items'] ) || ! is_array( $calc['items'] ) ) {
        return [];
    }

    $payload_items = [];

    foreach ( $calc['items'] as $item ) {
        $sku = isset( $item['sku'] ) ? trim( (string) $item['sku'] ) : '';
        $qty = isset( $item['qty'] ) ? (int) $item['qty'] : 0;

        if ( $sku === '' || $qty <= 0 ) {
            continue;
        }

        $payload_items[] = [
            'sku' => $sku,
            'qty' => $qty,
        ];
    }

    if ( empty( $payload_items ) ) {
        return [];
    }

    return [
        'surface_type'  => isset( $calc['surface_type'] ) ? (string) $calc['surface_type'] : ( isset( $answers['surface_type'] ) ? (string) $answers['surface_type'] : '' ),
        'system_label'  => isset( $answers['terrazzo_system'] ) ? (string) $answers['terrazzo_system'] : '',
        'project_title' => ad_materials_calc_build_project_title_from_answers( $answers ),
        'project_meta'  => ad_materials_calc_build_project_meta_from_answers( $answers ),
        'area_sqft'     => isset( $answers['total_area_sqft'] ) ? (float) $answers['total_area_sqft'] : 0,
        'items'         => $payload_items,
    ];
}

function ad_materials_calc_build_cart_payload_from_answers( array $answers ) {

    $calc = ad_materials_calc_get_calculation_from_answers( $answers );

    return ad_materials_calc_build_cart_payload_from_calc( $calc, $answers );
}

function ad_materials_calc_build_project_title_from_answers( array $answers ) {

    $parts         = [];
    $area_sqft     = isset( $answers['total_area_sqft'] ) ? (float) $answers['total_area_sqft'] : 0;
    $surface_type  = isset( $answers['surface_type'] ) ? trim( (string) $answers['surface_type'] ) : '';
    $system_label  = isset( $answers['terrazzo_system'] ) ? trim( (string) $answers['terrazzo_system'] ) : '';
    $pigment_color = isset( $answers['pigment_color'] ) ? trim( (string) $answers['pigment_color'] ) : '';

    if ( $area_sqft > 0 ) {
        $parts[] = rtrim( rtrim( number_format( $area_sqft, 1, '.', '' ), '0' ), '.' ) . ' sq.ft.';
    }

    if ( $surface_type !== '' ) {
        $parts[] = $surface_type;
    }

    if ( $system_label !== '' ) {
        $parts[] = $system_label;
    }

    if ( $pigment_color !== '' && ! ad_materials_calc_is_unpigmented( $pigment_color ) ) {
        $parts[] = $pigment_color;
    }

    $parts = array_values( array_unique( array_filter( $parts ) ) );

    if ( empty( $parts ) ) {
        return '';
    }

    return implode( ' / ', $parts );
}

function ad_materials_calc_build_project_meta_from_answers( array $answers ) {

    $surface_type = isset( $answers['surface_type'] ) ? (string) $answers['surface_type'] : '';
    $parts        = [];
    $labels       = ad_materials_calc_get_project_answer_labels();

    foreach ( ad_materials_calc_get_project_meta_keys( $surface_type ) as $key ) {
        if ( empty( $answers[$key] ) ) {
            continue;
        }

        $value = trim( (string) $answers[$key] );

        if ( $value === '' ) {
            continue;
        }

        if ( $key === 'pigment_color' && ad_materials_calc_is_unpigmented( $value ) ) {
            continue;
        }

        $label   = isset( $labels[$key] ) ? $labels[$key] : ucwords( str_replace( '_', ' ', $key ) );
        $parts[] = $label . ': ' . $value;
    }

    if ( empty( $parts ) ) {
        return '';
    }

    return implode( '<br>', array_map( 'esc_html', $parts ) );
}

function ad_materials_calc_get_project_meta_keys( $surface_type ) {

    $common_keys = [
        'finish_color',
    ];

    $surface_specific_keys = [
        'Interior / Exterior Walls'         => [
            'substrate_type_wall',
            'sheen_wall',
            'usage_level_wall',
        ],
        'Wet Area Walls'                    => [
            'existing_tile_wet_area',
            'tile_joint_condition_wet_area',
            'sheen_wet_area',
            'usage_level_wet_area',
        ],
        'Interior Floor'                    => [
            'existing_tile_interior_floor',
            'tile_joint_condition_interior_floor',
            'moisture_exposure_interior_floor',
            'usage_level_interior_floor',
            'sheen_interior_floor',
        ],
        'Exterior Floor (incl. Pool Decks)' => [
            'existing_tile_exterior_floor',
            'tile_joint_condition_exterior_floor',
            'usage_level_exterior_floor',
        ],
        'Pool Interior'                     => [],
    ];

    if ( empty( $surface_specific_keys[$surface_type] ) ) {
        return $common_keys;
    }

    return array_merge( $common_keys, $surface_specific_keys[$surface_type] );
}

function ad_materials_calc_get_project_answer_labels() {

    return [
        'finish_color'                        => 'Finish color',
        'substrate_type_wall'                 => 'Substrate',
        'sheen_wall'                          => 'Sheen',
        'usage_level_wall'                    => 'Usage Level',
        'existing_tile_wet_area'              => 'Existing Tile',
        'tile_joint_condition_wet_area'       => 'Tile Joint Condition',
        'sheen_wet_area'                      => 'Sheen',
        'usage_level_wet_area'                => 'Usage Level',
        'existing_tile_interior_floor'        => 'Existing Tile',
        'tile_joint_condition_interior_floor' => 'Tile Joint Condition',
        'moisture_exposure_interior_floor'    => 'Moisture Exposure',
        'usage_level_interior_floor'          => 'Usage Level',
        'sheen_interior_floor'                => 'Sheen',
        'existing_tile_exterior_floor'        => 'Existing Tile',
        'tile_joint_condition_exterior_floor' => 'Tile Joint Condition',
        'usage_level_exterior_floor'          => 'Usage Level',
    ];
}

function ad_materials_calc_get_colorant_quantity_data( $system_label, array $system_counters ) {

    $result = [
        'qty'                   => 0,
        'medium_small_fraction' => '',
        'fine_small_fraction'   => '',
        'micro_small_fraction'  => '',
    ];

    if ( $system_label === 'Terrazzo Granitech Medium' ) {
        $small_fraction                  = isset( $system_counters['medium_small'] ) ? ( (int) $system_counters['medium_small'] / 19 ) : 0;
        $result['qty']                   = ( isset( $system_counters['medium_large'] ) ? (int) $system_counters['medium_large'] : 0 ) + (int) ceil( $small_fraction );
        $result['medium_small_fraction'] = ad_materials_calc_format_fraction_debug( isset( $system_counters['medium_small'] ) ? $system_counters['medium_small'] : 0, 19 );

        return $result;
    }

    if ( $system_label === 'Terrazzo Granitech Fine' ) {
        $medium_small                    = isset( $system_counters['medium_small'] ) ? (int) $system_counters['medium_small'] : 0;
        $fine_small                      = isset( $system_counters['fine_small'] ) ? (int) $system_counters['fine_small'] : 0;
        $small_fraction                  = ( $medium_small / 19 ) + ( $fine_small / 10 );
        $result['qty']                   = ( isset( $system_counters['medium_large'] ) ? (int) $system_counters['medium_large'] : 0 ) + ( isset( $system_counters['fine_large'] ) ? (int) $system_counters['fine_large'] : 0 ) + (int) ceil( $small_fraction );
        $result['medium_small_fraction'] = ad_materials_calc_format_fraction_debug( $medium_small, 19 );
        $result['fine_small_fraction']   = ad_materials_calc_format_fraction_debug( $fine_small, 10 );

        return $result;
    }

    if ( $system_label === 'Terrazzo Granitech Microterrazzo' ) {
        $micro_small                    = isset( $system_counters['micro_small'] ) ? (int) $system_counters['micro_small'] : 0;
        $small_fraction                 = $micro_small / 22;
        $result['qty']                  = ( isset( $system_counters['micro_large'] ) ? (int) $system_counters['micro_large'] : 0 ) + (int) ceil( $small_fraction );
        $result['micro_small_fraction'] = ad_materials_calc_format_fraction_debug( $micro_small, 22 );

        return $result;
    }

    return $result;
}

function ad_materials_calc_format_fraction_debug( $numerator, $denominator ) {

    $numerator   = (int) $numerator;
    $denominator = (int) $denominator;

    if ( $denominator <= 0 ) {
        return '';
    }

    $decimal = round( $numerator / $denominator, 2 );

    // Remove useless trailing zeros, so 0.20 becomes 0.2
    $decimal_str = rtrim( rtrim( number_format( $decimal, 2, '.', '' ), '0' ), '.' );

    return $numerator . '/' . $denominator . ' (' . $decimal_str . ')';
}

/**
 * Product and table helpers
 */
function ad_materials_calc_render_results_table( array $items ) {

    if ( empty( $items ) ) {
        return '<div>No products were calculated.</div>';
    }

    $total = 0;

    $html = '<table class="shop_table shop_table_responsive shop-table-with-img cart woocommerce-cart-form__contents" cellspacing="0">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th scope="col" class="product-thumbnail"><span class="screen-reader-text">Thumbnail image</span></th>';
    $html .= '<th scope="col" class="product-name">Product</th>';
    $html .= '<th scope="col" class="product-price">Price</th>';
    $html .= '<th scope="col" class="product-quantity">Quantity</th>';
    $html .= '<th scope="col" class="product-subtotal">Subtotal</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    foreach ( $items as $item ) {
        $total += (float) $item['partial'];

        $product_name_html = esc_html( $item['name'] );

        if ( ! empty( $item['url'] ) ) {
            $product_name_html = '<a href="' . esc_url( $item['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $item['name'] ) . '</a>';
        }

        $html .= '<tr class="woocommerce-cart-form__cart-item cart_item tc-container-table-product">';

        $html .= '<td class="product-thumbnail">';
        $html .= $item['thumb'];
        $html .= '</td>';

        $html .= '<td class="product-name" role="rowheader" data-title="Product">';
        $html .= $product_name_html;
        $html .= '<div style="font-size:12px;opacity:.75;">' . esc_html( $item['sku'] ) . '</div>';
        $html .= '</td>';

        $html .= '<td class="product-price" data-title="Price">';
        $html .= wp_kses_post( wc_price( $item['unit_price'] ) );
        $html .= '</td>';

        $html .= '<td class="product-quantity" data-title="Quantity">';
        $html .= esc_html( $item['qty'] );
        $html .= '</td>';

        $html .= '<td class="product-subtotal" data-title="Subtotal">';
        $html .= wp_kses_post( wc_price( $item['partial'] ) );
        $html .= '</td>';

        $html .= '</tr>';
    }

    $html .= '<tr class="wd-cart-action-row">';
    $html .= '<td colspan="4" style="text-align:right;"><strong>Total</strong></td>';
    $html .= '<td class="product-subtotal"><strong>' . wp_kses_post( wc_price( $total ) ) . '</strong></td>';
    $html .= '</tr>';

    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}

function ad_materials_calc_build_line_item_from_product( $product, $qty ) {

    $qty = (int) $qty;

    $unit_price = (float) wc_get_price_to_display( $product );
    $partial    = $unit_price * $qty;

    return [
        'thumb'      => ad_materials_calc_get_product_thumb_html( $product ),
        'name'       => ad_materials_calc_get_line_item_display_name( $product ),
        'url'        => ad_materials_calc_get_line_item_url( $product ),
        'sku'        => (string) $product->get_sku(),
        'qty'        => $qty,
        'unit_price' => $unit_price,
        'partial'    => $partial,
    ];
}

function ad_materials_calc_get_line_item_display_name( $product ) {

    if ( ! $product ) {
        return '';
    }

    // For variations, show parent product name + readable variation attributes
    if ( $product->is_type( 'variation' ) ) {
        $parent_id = $product->get_parent_id();
        $parent    = $parent_id ? wc_get_product( $parent_id ) : false;

        $base_name = $parent ? $parent->get_name() : $product->get_name();

        $variation_text = wc_get_formatted_variation( $product, true, false, true );
        $variation_text = wp_strip_all_tags( $variation_text );
        $variation_text = trim( preg_replace( '/\s+/', ' ', $variation_text ) );

        if ( $variation_text !== '' ) {
            return $base_name . ' - ' . $variation_text;
        }

        return $base_name;
    }

    return $product->get_name();
}

function ad_materials_calc_get_line_item_url( $product ) {

    if ( ! $product ) {
        return '';
    }

    // Simple product
    if ( ! $product->is_type( 'variation' ) ) {
        return get_permalink( $product->get_id() );
    }

    // Variation product: link to parent + preselected attributes
    $parent_id = $product->get_parent_id();

    if ( ! $parent_id ) {
        return '';
    }

    $url = get_permalink( $parent_id );

    if ( ! $url ) {
        return '';
    }

    $attributes = $product->get_variation_attributes();

    if ( empty( $attributes ) || ! is_array( $attributes ) ) {
        return $url;
    }

    $query_args = [];

    foreach ( $attributes as $attribute_name => $attribute_value ) {
        if ( $attribute_value === '' ) {
            continue;
        }

        // WooCommerce usually already gives keys like attribute_pa_color
        $query_args[$attribute_name] = $attribute_value;
    }

    if ( empty( $query_args ) ) {
        return $url;
    }

    return add_query_arg( $query_args, $url );
}

function ad_materials_calc_get_product_thumb_html( $product ) {

    $image_id = (int) $product->get_image_id();

    if ( ! $image_id && method_exists( $product, 'get_parent_id' ) && $product->get_parent_id() ) {
        $parent = wc_get_product( $product->get_parent_id() );

        if ( $parent ) {
            $image_id = (int) $parent->get_image_id();
        }
    }

    if ( ! $image_id ) {
        return '';
    }

    $image_html = wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, [
        'style' => 'width:80px;min-width:80px;max-width:80px;height:auto;',
    ] );

    $url = '';

    if ( function_exists( 'ad_materials_calc_get_line_item_url' ) ) {
        $url = ad_materials_calc_get_line_item_url( $product );
    }

    if ( $url ) {
        return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . $image_html . '</a>';
    }

    return $image_html;
}

function ad_materials_calc_get_product_by_sku( $sku ) {

    static $product_cache = [];

    $sku = trim( (string) $sku );

    if ( $sku === '' ) {
        return false;
    }

    if ( array_key_exists( $sku, $product_cache ) ) {
        return $product_cache[$sku];
    }

    $product_id = wc_get_product_id_by_sku( $sku );

    if ( ! $product_id ) {
        $product_cache[$sku] = false;

        return false;
    }

    $product_cache[$sku] = wc_get_product( $product_id );

    return $product_cache[$sku];
}

function ad_materials_calc_get_product_price_by_sku( $sku ) {

    static $price_cache = [];

    $sku = trim( (string) $sku );

    if ( $sku === '' ) {
        return 0.0;
    }

    if ( array_key_exists( $sku, $price_cache ) ) {
        return $price_cache[$sku];
    }

    $product = ad_materials_calc_get_product_by_sku( $sku );

    if ( ! $product ) {
        $price_cache[$sku] = 0.0;

        return 0.0;
    }

    $price_cache[$sku] = (float) wc_get_price_to_display( $product );

    return $price_cache[$sku];
}

function ad_materials_calc_expand_combo_to_line_items( array $combo, array $sku_map ) {

    $items = [];

    if ( empty( $combo['bucket_counts'] ) ) {
        return $items;
    }

    foreach ( $combo['bucket_counts'] as $bucket_size => $count ) {
        $count = (int) $count;

        if ( $count <= 0 ) {
            continue;
        }

        $bucket_key = (int) $bucket_size;

        if ( empty( $sku_map[$bucket_key] ) ) {
            continue;
        }

        $product = ad_materials_calc_get_product_by_sku( $sku_map[$bucket_key] );

        if ( ! $product ) {
            continue;
        }

        $items[] = ad_materials_calc_build_line_item_from_product( $product, $count );
    }

    return $items;
}

function ad_materials_calc_get_combo_bucket_count( array $combo, $bucket_size ) {

    if ( empty( $combo['bucket_counts'] ) ) {
        return 0;
    }

    foreach ( $combo['bucket_counts'] as $size => $count ) {
        if ( (int) $size === (int) $bucket_size ) {
            return (int) $count;
        }
    }

    return 0;
}

function ad_materials_calc_is_unpigmented( $pigment_label ) {

    $pigment_label = trim( (string) $pigment_label );

    return ( $pigment_label === '' || strtolower( $pigment_label ) === 'unpigmented' || $pigment_label === '0' );
}

/**
 * Resolve the selected pigment label to the actual variation product object
 * Medium uses product 9838
 * Fine and Microterrazzo use product 9932
 */
function ad_materials_calc_find_pigment_product_by_label( $system_label, $pigment_label ) {

    static $pigment_cache = [];

    $pigment_label = trim( (string) $pigment_label );

    if ( ad_materials_calc_is_unpigmented( $pigment_label ) ) {
        return false;
    }

    $cache_key = $system_label . '|' . $pigment_label;

    if ( array_key_exists( $cache_key, $pigment_cache ) ) {
        return $pigment_cache[$cache_key];
    }

    $parent_product_id = 0;

    if ( $system_label === 'Terrazzo Granitech Medium' ) {
        $parent_product_id = 9838;
    } elseif ( $system_label === 'Terrazzo Granitech Fine' || $system_label === 'Terrazzo Granitech Microterrazzo' ) {
        $parent_product_id = 9932;
    } else {
        $pigment_cache[$cache_key] = false;

        return false;
    }

    $parent = wc_get_product( $parent_product_id );

    if ( ! $parent || ! $parent->is_type( 'variable' ) ) {
        $pigment_cache[$cache_key] = false;

        return false;
    }

    $variations = $parent->get_available_variations();

    foreach ( $variations as $variation_data ) {
        $variation_id = isset( $variation_data['variation_id'] ) ? (int) $variation_data['variation_id'] : 0;

        if ( ! $variation_id ) {
            continue;
        }

        $variation = wc_get_product( $variation_id );

        if ( ! $variation ) {
            continue;
        }

        $color_slug = $variation->get_attribute( 'pa_color' );

        if ( $color_slug === '' ) {
            continue;
        }

        $term = get_term_by( 'slug', $color_slug, 'pa_color' );

        if ( ! $term || is_wp_error( $term ) ) {
            continue;
        }

        if ( $term->name === $pigment_label ) {
            $pigment_cache[$cache_key] = $variation;

            return $pigment_cache[$cache_key];
        }
    }

    $pigment_cache[$cache_key] = false;

    return false;
}

function ad_materials_calc_get_silica_notice_html( $silica_packages ) {

    $silica_packages = (int) $silica_packages;

    if ( $silica_packages <= 0 ) {
        return '';
    }

    $notice = '<strong>' . esc_html__( 'Silica sand notice:', 'ad-materials-calculator' ) . '</strong> ';
    $notice .= sprintf(
        wp_kses_post(
            _n(
                'For this project, <strong>%d</strong> package of silica sand will be needed, sold separately.',
                'For this project, <strong>%d</strong> packages of silica sand will be needed, sold separately.',
                $silica_packages,
                'ad-materials-calculator'
            )
        ),
        $silica_packages
    );
    $notice .= ' ';
    $notice .= wp_kses_post(
        __( 'For the calculations, a <strong>50lb / 23kg</strong> silica sand package was used.', 'ad-materials-calculator' )
    );

    $notice .= '<br><br><strong>' . esc_html__( 'Indicative Sources For Silica Sand', 'ad-materials-calculator' ) . '</strong>';
    $notice .= '<br><a href="https://www.homedepot.com/p/Quikrete-50-lb-All-Purpose-Sand-115251/100318450" target="_blank" rel="noopener noreferrer">Home Depot - Quikrete 50 lb All-Purpose Sand</a>';
    $notice .= '<br><a href="https://www.oeg.net/en/quartz-filter-sand-0-4-0-8-mm-650001244" target="_blank" rel="noopener noreferrer">OEG - Quartz Filter Sand 0.4–0.8 mm</a>';
    $notice .= '<br><a href="https://floorguardproducts.com/large-silica-sand-20-40-mesh-50-lb-bag/" target="_blank" rel="noopener noreferrer">Floor Guard Products - Large Silica Sand 20/40 Mesh 50 lb Bag</a>';

    return $notice;
}

/**
 * Bucket helpers
 */
function ad_materials_calc_get_best_2bucket_combo( $target_weight, array $bucket_prices ) {

    $target_weight = (float) $target_weight;

    if ( count( $bucket_prices ) !== 2 ) {
        return ['bucket_counts' => []];
    }

    $normalized = [];

    foreach ( $bucket_prices as $bucket_kg => $price ) {
        $normalized[(float) $bucket_kg] = (float) $price;
    }

    krsort( $normalized, SORT_NUMERIC );

    $sizes  = array_keys( $normalized );
    $prices = array_values( $normalized );

    $large_bucket_kg    = (float) $sizes[0];
    $small_bucket_kg    = (float) $sizes[1];
    $large_bucket_price = (float) $prices[0];
    $small_bucket_price = (float) $prices[1];

    $large_count_base = (int) floor( $target_weight / $large_bucket_kg );
    $remainder        = $target_weight - ( $large_count_base * $large_bucket_kg );

    if ( $remainder == 0 ) {
        return [
            'bucket_counts' => [
                $large_bucket_kg => $large_count_base,
                $small_bucket_kg => 0,
            ],
        ];
    }

    $small_count_a = (int) ceil( $remainder / $small_bucket_kg );
    $large_count_a = $large_count_base;
    $weight_a      = ( $large_count_a * $large_bucket_kg ) + ( $small_count_a * $small_bucket_kg );
    $price_a       = ( $large_count_a * $large_bucket_price ) + ( $small_count_a * $small_bucket_price );

    $large_count_b = $large_count_base + 1;
    $small_count_b = 0;
    $weight_b      = $large_count_b * $large_bucket_kg;
    $price_b       = $large_count_b * $large_bucket_price;

    if ( $price_a < $price_b ) {
        return [
            'bucket_counts' => [
                $large_bucket_kg => $large_count_a,
                $small_bucket_kg => $small_count_a,
            ],
        ];
    }

    if ( $price_b < $price_a ) {
        return [
            'bucket_counts' => [
                $large_bucket_kg => $large_count_b,
                $small_bucket_kg => $small_count_b,
            ],
        ];
    }

    $excess_a = $weight_a - $target_weight;
    $excess_b = $weight_b - $target_weight;

    if ( $excess_a <= $excess_b ) {
        return [
            'bucket_counts' => [
                $large_bucket_kg => $large_count_a,
                $small_bucket_kg => $small_count_a,
            ],
        ];
    }

    return [
        'bucket_counts' => [
            $large_bucket_kg => $large_count_b,
            $small_bucket_kg => $small_count_b,
        ],
    ];
}

function ad_materials_calc_get_best_3bucket_combo( $target_weight, array $bucket_prices ) {

    $target_weight = (float) $target_weight;

    if ( count( $bucket_prices ) !== 3 ) {
        return ['bucket_counts' => []];
    }

    $normalized = [];

    foreach ( $bucket_prices as $bucket_kg => $price ) {
        $normalized[(float) $bucket_kg] = (float) $price;
    }

    krsort( $normalized, SORT_NUMERIC );

    $sizes  = array_keys( $normalized );
    $prices = array_values( $normalized );

    $large_kg  = (float) $sizes[0];
    $middle_kg = (float) $sizes[1];
    $small_kg  = (float) $sizes[2];

    $large_p  = (float) $prices[0];
    $middle_p = (float) $prices[1];
    $small_p  = (float) $prices[2];

    $best = null;

    $max_large  = (int) ceil( $target_weight / $large_kg ) + 1;
    $max_middle = (int) ceil( $target_weight / $middle_kg ) + 2;

    for ( $l = 0; $l <= $max_large; $l++ ) {
        for ( $m = 0; $m <= $max_middle; $m++ ) {
            $covered   = ( $l * $large_kg ) + ( $m * $middle_kg );
            $remaining = $target_weight - $covered;

            $s = 0;

            if ( $remaining > 0 ) {
                $s = (int) ceil( $remaining / $small_kg );
            }

            $total_weight = ( $l * $large_kg ) + ( $m * $middle_kg ) + ( $s * $small_kg );

            if ( $total_weight < $target_weight ) {
                continue;
            }

            $total_price = ( $l * $large_p ) + ( $m * $middle_p ) + ( $s * $small_p );
            $excess      = $total_weight - $target_weight;
            $bucket_cnt  = $l + $m + $s;

            if ( $best === null ) {
                $best = [
                    'bucket_counts' => [
                        $large_kg  => $l,
                        $middle_kg => $m,
                        $small_kg  => $s,
                    ],
                    'price'         => $total_price,
                    'excess'        => $excess,
                    'bucket_cnt'    => $bucket_cnt,
                ];
                continue;
            }

            if ( $total_price < $best['price'] ) {
                $best = [
                    'bucket_counts' => [
                        $large_kg  => $l,
                        $middle_kg => $m,
                        $small_kg  => $s,
                    ],
                    'price'         => $total_price,
                    'excess'        => $excess,
                    'bucket_cnt'    => $bucket_cnt,
                ];
                continue;
            }

            if ( $total_price == $best['price'] && $excess < $best['excess'] ) {
                $best = [
                    'bucket_counts' => [
                        $large_kg  => $l,
                        $middle_kg => $m,
                        $small_kg  => $s,
                    ],
                    'price'         => $total_price,
                    'excess'        => $excess,
                    'bucket_cnt'    => $bucket_cnt,
                ];
                continue;
            }

            if ( $total_price == $best['price'] && $excess == $best['excess'] && $bucket_cnt < $best['bucket_cnt'] ) {
                $best = [
                    'bucket_counts' => [
                        $large_kg  => $l,
                        $middle_kg => $m,
                        $small_kg  => $s,
                    ],
                    'price'         => $total_price,
                    'excess'        => $excess,
                    'bucket_cnt'    => $bucket_cnt,
                ];
            }
        }
    }

    if ( ! $best ) {
        return ['bucket_counts' => []];
    }

    return [
        'bucket_counts' => $best['bucket_counts'],
    ];
}