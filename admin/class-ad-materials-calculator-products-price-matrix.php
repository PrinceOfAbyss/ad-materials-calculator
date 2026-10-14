<?php

/**
 * The products price matrix settings tab.
 *
 * @link       https://agencydigital.tech/
 * @since      1.0.3
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/admin
 */

/**
 * Renders and saves a consolidated WooCommerce/calculator products price matrix.
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/admin
 * @author     Agency Digital <info@agencydigital.tech>
 */
class Ad_Materials_Calculator_Products_Price_Matrix {

    /**
     * Meta key used to persist calculator-only applicator prices.
     *
     * @since    1.0.3
     * @var      string
     */
    private $applicator_price_meta_key = '_ad_materials_calc_applicator_price';

    /**
     * Meta key used to persist calculator-only distributor prices.
     *
     * @since    1.0.3
     * @var      string
     */
    private $distributor_price_meta_key = '_ad_materials_calc_distributor_price';

    /**
     * Add the Products Price Matrix tab to WooCommerce settings.
     *
     * @since    1.0.3
     * @param    array $tabs Existing WooCommerce settings tabs.
     * @return   array
     */
    public function add_woocommerce_settings_tab( $tabs ) {

        $tabs['products-price-matrix'] = __( 'Products Price Matrix', 'ad-materials-calculator' );

        return $tabs;
    }

    /**
     * Render the custom Products Price Matrix WooCommerce settings tab.
     *
     * @since    1.0.3
     */
    public function render_woocommerce_settings_tab() {

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage WooCommerce prices.', 'ad-materials-calculator' ) );
        }

        if ( ! function_exists( 'wc_get_products' ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'WooCommerce must be active to render the products price matrix.', 'ad-materials-calculator' ) . '</p></div>';

            return;
        }

        echo '<div class="ad-mc-settings-page ad-mc-products-price-matrix">';
        echo '<p class="ad-mc-settings-intro">' . esc_html__( 'Review and edit the normal WooCommerce regular price, calculator Applicator price, and calculator Distributor price in one place.', 'ad-materials-calculator' ) . '</p>';

        $products = $this->get_matrix_products();

        if ( empty( $products ) ) {
            echo '<section class="ad-mc-settings-case">';
            echo '<header class="ad-mc-settings-case__header"><h3>' . esc_html__( 'No products found', 'ad-materials-calculator' ) . '</h3></header>';
            echo '<div class="ad-mc-settings-clause"><p>' . esc_html__( 'No calculator products were found.', 'ad-materials-calculator' ) . '</p></div>';
            echo '</section>';
            echo '</div>';

            return;
        }

        foreach ( $products as $product ) {
            $this->render_product_matrix( $product );
        }

        echo '</div>';
    }

    /**
     * Save the custom Products Price Matrix WooCommerce settings tab.
     *
     * @since    1.0.3
     */
    public function save_woocommerce_settings_tab() {

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        if ( ! function_exists( 'wc_get_product' ) ) {
            $this->add_settings_error( __( 'The price matrix could not be saved because WooCommerce is not available.', 'ad-materials-calculator' ) );

            return;
        }

        $applicator_price_meta_key  = $this->get_applicator_price_meta_key();
        $distributor_price_meta_key = $this->get_distributor_price_meta_key();
        $submitted_prices           = isset( $_POST['ad_materials_calculator_products_price_matrix'] ) && is_array( $_POST['ad_materials_calculator_products_price_matrix'] ) ? wp_unslash( $_POST['ad_materials_calculator_products_price_matrix'] ) : [];

        $parent_ids = [];

        foreach ( $submitted_prices as $product_id => $price_values ) {
            $product_id = absint( $product_id );

            if ( $product_id <= 0 || ! is_array( $price_values ) ) {
                continue;
            }

            $product = wc_get_product( $product_id );

            if ( ! $product || ! in_array( $product->get_type(), ['simple', 'variation'], true ) ) {
                continue;
            }

            if ( ! $this->is_product_used_by_calculator( $product ) ) {
                continue;
            }

            $woo_regular_price = isset( $price_values['woo_regular'] ) ? $this->sanitize_price_value( $price_values['woo_regular'] ) : '';
            $applicator_price  = isset( $price_values['applicator'] ) ? $this->sanitize_price_value( $price_values['applicator'] ) : '';
            $distributor_price = isset( $price_values['distributor'] ) ? $this->sanitize_price_value( $price_values['distributor'] ) : '';

            $this->save_woocommerce_regular_price( $product, $woo_regular_price );
            $this->save_post_meta_price( $product_id, $applicator_price_meta_key, $applicator_price );
            $this->save_post_meta_price( $product_id, $distributor_price_meta_key, $distributor_price );

            if ( $product->is_type( 'variation' ) ) {
                $parent_id = absint( $product->get_parent_id() );

                if ( $parent_id > 0 ) {
                    $parent_ids[] = $parent_id;
                }
            }
        }

        foreach ( array_unique( $parent_ids ) as $parent_id ) {
            WC_Product_Variable::sync( $parent_id );
            wc_delete_product_transients( $parent_id );
        }
    }

    /**
     * Render one product card and its simple/variation rows.
     *
     * @since    1.0.3
     * @param    WC_Product $product Product object.
     */
    private function render_product_matrix( $product ) {

        $rows = $this->get_product_price_rows( $product );

        if ( empty( $rows ) ) {
            return;
        }

        echo '<section class="ad-mc-settings-case ad-mc-products-price-matrix-product">';
        echo '<header class="ad-mc-settings-case__header">';
        echo '<h3><a href="' . esc_url( $this->get_product_edit_url( $product ) ) . '">' . esc_html( $product->get_name() ) . '</a></h3>';
        echo '<p class="ad-mc-products-price-matrix-product__meta">';
        echo esc_html( sprintf( __( 'Product ID: %d', 'ad-materials-calculator' ), $product->get_id() ) );

        if ( $product->get_sku() ) {
            echo ' <span aria-hidden="true">|</span> ' . esc_html( sprintf( __( 'SKU: %s', 'ad-materials-calculator' ), $product->get_sku() ) );
        }

        if ( $product->is_type( 'variable' ) ) {
            echo ' <span aria-hidden="true">|</span> ' . esc_html(
                sprintf(
                    _n( '%d variation', '%d variations', count( $rows ), 'ad-materials-calculator' ),
                    count( $rows )
                )
            );
        } else {
            echo ' <span aria-hidden="true">|</span> ' . esc_html__( 'Simple product', 'ad-materials-calculator' );
        }

        echo '</p>';
        echo '</header>';
        echo '<div class="ad-mc-settings-clause">';
        echo '<div class="ad-mc-products-price-matrix-table-wrap">';
        echo '<table class="widefat striped ad-mc-settings-table ad-mc-products-price-matrix-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html( $product->is_type( 'variable' ) ? __( 'Variation', 'ad-materials-calculator' ) : __( 'Product', 'ad-materials-calculator' ) ) . '</th>';
        echo '<th>' . esc_html__( 'Woo Regular Price', 'ad-materials-calculator' ) . '</th>';
        echo '<th>' . esc_html__( 'Applicator Price', 'ad-materials-calculator' ) . '</th>';
        echo '<th>' . esc_html__( 'Distributor Price', 'ad-materials-calculator' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ( $rows as $row ) {
            $this->render_price_row( $row );
        }

        echo '</tbody></table>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
    }

    /**
     * Render one price row.
     *
     * @since    1.0.3
     * @param    array $row Row data.
     */
    private function render_price_row( array $row ) {

        $product = $row['product'];

        echo '<tr>';
        echo '<td class="ad-mc-settings-table__label ad-mc-products-price-matrix-table__label">';

        if ( $row['type'] === 'variation' ) {
            echo '<span class="ad-mc-settings-table__material-name">' . esc_html( $row['label'] ) . '</span>';
            echo '<span class="ad-mc-settings-table__material-hint">' . esc_html( $this->get_sku_label( $product ) ) . '</span>';
            echo '<span class="ad-mc-products-price-matrix-table__id">' . esc_html( sprintf( __( 'Variation ID: %d', 'ad-materials-calculator' ), $product->get_id() ) ) . '</span>';
        } else {
            echo '<span class="ad-mc-settings-table__material-name">' . esc_html__( 'Simple product', 'ad-materials-calculator' ) . '</span>';
            echo '<span class="ad-mc-settings-table__material-hint">' . esc_html( $this->get_sku_label( $product ) ) . '</span>';
        }

        echo '</td>';
        echo '<td>' . $this->get_price_input_html( $product->get_id(), 'woo_regular', $row['woo_regular_price'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<td>' . $this->get_price_input_html( $product->get_id(), 'applicator', $row['applicator_price'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<td>' . $this->get_price_input_html( $product->get_id(), 'distributor', $row['distributor_price'] ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</tr>';
    }

    /**
     * Get the admin edit URL for a product.
     *
     * @since    1.0.3
     * @param    WC_Product $product Product object.
     * @return   string
     */
    private function get_product_edit_url( $product ) {

        return get_edit_post_link( $product->get_id(), '' );
    }

    /**
     * Get simple and variable products for the matrix.
     *
     * @since    1.0.3
     * @return   WC_Product[]
     */
    private function get_matrix_products() {

        $products = wc_get_products(
            [
                'limit'   => -1,
                'orderby' => 'title',
                'order'   => 'ASC',
                'status'  => ['publish', 'private', 'draft'],
                'type'    => ['simple', 'variable'],
            ]
        );

        return array_values(
            array_filter(
                $products,
                function ( $product ) {
                    return ! empty( $this->get_product_price_rows( $product ) );
                }
            )
        );
    }

    /**
     * Build the rows for one simple or variable product.
     *
     * @since    1.0.3
     * @param    WC_Product $product Product object.
     * @return   array
     */
    private function get_product_price_rows( $product ) {

        if ( $product->is_type( 'simple' ) ) {
            if ( ! $this->is_product_used_by_calculator( $product ) ) {
                return [];
            }

            return [
                $this->get_price_row_data(
                    $product,
                    '',
                    'simple'
                ),
            ];
        }

        if ( ! $product->is_type( 'variable' ) ) {
            return [];
        }

        $rows = [];

        foreach ( $product->get_children() as $variation_id ) {
            $variation = wc_get_product( $variation_id );

            if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
                continue;
            }

            if ( ! $this->is_product_used_by_calculator( $variation ) ) {
                continue;
            }

            $rows[] = $this->get_price_row_data(
                $variation,
                $this->get_variation_label( $variation ),
                'variation'
            );
        }

        usort( $rows, [$this, 'sort_price_rows_by_woo_regular_price'] );

        return $rows;
    }

    /**
     * Sort price rows by WooCommerce regular price.
     *
     * @since    1.0.3
     * @param    array $first_row First row.
     * @param    array $second_row Second row.
     * @return   int
     */
    private function sort_price_rows_by_woo_regular_price( array $first_row, array $second_row ) {

        $first_price  = $this->get_sortable_price_value( $first_row['woo_regular_price'] );
        $second_price = $this->get_sortable_price_value( $second_row['woo_regular_price'] );

        if ( $first_price === $second_price ) {
            return strnatcasecmp( $first_row['label'], $second_row['label'] );
        }

        return $first_price <=> $second_price;
    }

    /**
     * Convert a price value into a sortable number.
     *
     * Empty prices sort after filled prices.
     *
     * @since    1.0.3
     * @param    string $price Price value.
     * @return   float
     */
    private function get_sortable_price_value( $price ) {

        if ( '' === $price || null === $price ) {
            return PHP_FLOAT_MAX;
        }

        return (float) $price;
    }

    /**
     * Build row data for one product or variation.
     *
     * @since    1.0.3
     * @param    WC_Product $product Product or variation object.
     * @param    string     $label Row label.
     * @param    string     $type Row product type.
     * @return   array
     */
    private function get_price_row_data( $product, $label, $type ) {

        return [
            'product'           => $product,
            'label'             => $label,
            'type'              => $type,
            'woo_regular_price' => $product->get_regular_price( 'edit' ),
            'applicator_price'  => get_post_meta( $product->get_id(), $this->get_applicator_price_meta_key(), true ),
            'distributor_price' => get_post_meta( $product->get_id(), $this->get_distributor_price_meta_key(), true ),
        ];
    }

    /**
     * Check whether a product or variation is part of the calculator product set.
     *
     * @since    1.0.3
     * @param    WC_Product $product Product object.
     * @return   bool
     */
    private function is_product_used_by_calculator( $product ) {

        if ( ! $product ) {
            return false;
        }

        $sku = trim( (string) $product->get_sku() );

        if ( $sku !== '' && in_array( $sku, $this->get_calculator_product_skus(), true ) ) {
            return true;
        }

        if ( $product->is_type( 'variation' ) && in_array( (int) $product->get_parent_id(), $this->get_calculator_variation_parent_product_ids(), true ) ) {
            return true;
        }

        return in_array( (int) $product->get_id(), $this->get_calculator_variation_parent_product_ids(), true );
    }

    /**
     * Return hardcoded SKUs that can be calculated into a cart.
     *
     * Pigment and stone variations are covered by parent product IDs because their SKUs are dynamic.
     *
     * @since    1.0.3
     * @return   string[]
     */
    private function get_calculator_product_skus() {

        return [
            '5200433500008',
            '5200433500015',
            '5200433500022',
            '5200433500046',
            '5200433500053',
            '5200433500534',
            '5200433500541',
            '5200433500565',
            '5200433500572',
            '5200433500589',
            '5200433500619',
            '5200433501685',
            '5200433501692',
            '5200433501769',
            '5200433501770',
            '5200433501773',
            '5200433501776',
            '5200433501777',
            '5200433501785',
            '5200433501786',
            '5200433501795',
            '5200433501796',
            '5200433501809',
            '5200433501810',
            '5200433501811',
            '5200433501812',
            '5200433501814',
            '5200433501815',
            '5200433501816',
            '5200433501948',
            '5200433501949',
            'Armour Coat',
        ];
    }

    /**
     * Return variable parent product IDs used by the calculator.
     *
     * @since    1.0.3
     * @return   int[]
     */
    private function get_calculator_variation_parent_product_ids() {

        return [
            9838,
            9932,
            14739,
            14769,
        ];
    }

    /**
     * Save the WooCommerce regular price for one product or variation.
     *
     * @since    1.0.3
     * @param    WC_Product $product Product object.
     * @param    string     $regular_price Regular price value.
     */
    private function save_woocommerce_regular_price( $product, $regular_price ) {

        $product->set_regular_price( $regular_price );

        if ( $product->is_on_sale( 'edit' ) ) {
            $product->set_price( $product->get_sale_price( 'edit' ) );
        } else {
            $product->set_price( $regular_price );
        }

        $product->save();
        wc_delete_product_transients( $product->get_id() );
    }

    /**
     * Save or remove a calculator price meta value.
     *
     * @since    1.0.3
     * @param    int    $product_id Product or variation ID.
     * @param    string $meta_key Meta key.
     * @param    string $price Price value.
     */
    private function save_post_meta_price( $product_id, $meta_key, $price ) {

        if ( '' === $price ) {
            delete_post_meta( $product_id, $meta_key );

            return;
        }

        update_post_meta( $product_id, $meta_key, $price );
    }

    /**
     * Sanitize a submitted price string.
     *
     * @since    1.0.3
     * @param    mixed $value Raw price value.
     * @return   string
     */
    private function sanitize_price_value( $value ) {

        if ( ! is_scalar( $value ) ) {
            return '';
        }

        $value = trim( wc_clean( (string) $value ) );

        if ( '' === $value ) {
            return '';
        }

        return wc_format_decimal( $value );
    }

    /**
     * Build a price input.
     *
     * @since    1.0.3
     * @param    int    $product_id Product or variation ID.
     * @param    string $price_key Price field key.
     * @param    string $value Current price value.
     * @return   string
     */
    private function get_price_input_html( $product_id, $price_key, $value ) {

        return sprintf(
            '<input type="text" inputmode="decimal" class="regular-text ad-mc-products-price-matrix__price-input" name="ad_materials_calculator_products_price_matrix[%1$d][%2$s]" value="%3$s" />',
            absint( $product_id ),
            esc_attr( $price_key ),
            esc_attr( $this->format_price_input_value( $value ) )
        );
    }

    /**
     * Format a price for editing.
     *
     * @since    1.0.3
     * @param    string $value Raw price value.
     * @return   string
     */
    private function format_price_input_value( $value ) {

        if ( '' === $value || null === $value ) {
            return '';
        }

        return wc_format_localized_price( $value );
    }

    /**
     * Get a readable label for a variation.
     *
     * @since    1.0.3
     * @param    WC_Product_Variation $variation Variation object.
     * @return   string
     */
    private function get_variation_label( $variation ) {

        $label_parts = [];
        $attributes  = $variation->get_variation_attributes();

        foreach ( $attributes as $attribute_key => $attribute_value ) {
            $taxonomy        = preg_replace( '/^attribute_/', '', (string) $attribute_key );
            $attribute_label = wc_attribute_label( $taxonomy, $variation );
            $value_label     = $this->get_variation_attribute_value_label( $variation, $taxonomy, $attribute_value, $attribute_label );

            if ( '' === $attribute_label || '' === $value_label ) {
                continue;
            }

            $label_parts[] = sprintf(
                '%1$s: %2$s',
                $attribute_label,
                $value_label
            );
        }

        if ( ! empty( $label_parts ) ) {
            return implode( ' / ', $label_parts );
        }

        $label = wc_get_formatted_variation( $variation, true, true, true );
        $label = trim( wp_strip_all_tags( $label ) );

        return '' !== $label ? $label : $variation->get_name();
    }

    /**
     * Get a readable variation attribute value label.
     *
     * @since    1.0.3
     * @param    WC_Product_Variation $variation Variation object.
     * @param    string               $taxonomy Attribute taxonomy or custom key.
     * @param    string               $attribute_value Raw attribute value.
     * @param    string               $attribute_label Attribute label.
     * @return   string
     */
    private function get_variation_attribute_value_label( $variation, $taxonomy, $attribute_value, $attribute_label ) {

        $attribute_value = (string) $attribute_value;

        if ( '' === $attribute_value ) {
            return sprintf(
                __( 'Any %s', 'ad-materials-calculator' ),
                $attribute_label
            );
        }

        $variation_value = $variation->get_attribute( $taxonomy );

        if ( '' !== $variation_value ) {
            return $variation_value;
        }

        if ( taxonomy_exists( $taxonomy ) ) {
            $term = get_term_by( 'slug', $attribute_value, $taxonomy );

            if ( $term && ! is_wp_error( $term ) ) {
                return $term->name;
            }
        }

        return wc_clean( rawurldecode( $attribute_value ) );
    }

    /**
     * Return a compact SKU label.
     *
     * @since    1.0.3
     * @param    WC_Product $product Product object.
     * @return   string
     */
    private function get_sku_label( $product ) {

        $sku = $product->get_sku();

        if ( '' === $sku ) {
            return __( 'SKU: Not set', 'ad-materials-calculator' );
        }

        return sprintf( __( 'SKU: %s', 'ad-materials-calculator' ), $sku );
    }

    /**
     * Return the applicator calculator price meta key.
     *
     * @since    1.0.3
     * @return   string
     */
    private function get_applicator_price_meta_key() {

        return $this->applicator_price_meta_key;
    }

    /**
     * Return the distributor calculator price meta key.
     *
     * @since    1.0.3
     * @return   string
     */
    private function get_distributor_price_meta_key() {

        return $this->distributor_price_meta_key;
    }

    /**
     * Add a WooCommerce settings error when available.
     *
     * @since    1.0.3
     * @param    string $message Error message.
     */
    private function add_settings_error( $message ) {

        if ( class_exists( 'WC_Admin_Settings' ) ) {
            WC_Admin_Settings::add_error( $message );
        }
    }
}