<?php

/**
 * Share links admin list table.
 *
 * @link       https://agencydigital.tech/
 * @since      1.0.0
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/includes
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Displays calculator share links in the WordPress admin.
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/includes
 * @author     Agency Digital <info@agencydigital.tech>
 */
class Ad_Materials_Calculator_Share_Links_List_Table extends WP_List_Table {

    /**
     * Set up the share links table.
     *
     * @since    1.0.0
     */
    public function __construct() {

        parent::__construct(
            [
                'singular' => 'calculator share link',
                'plural'   => 'calculator share links',
                'ajax'     => false,
            ]
        );
    }

    /**
     * Define the table columns.
     *
     * @since    1.0.0
     * @return   array
     */
    public function get_columns() {

        return [
            'id'                 => __( 'ID', 'ad-materials-calculator' ),
            'summary'            => __( 'Cart Summary', 'ad-materials-calculator' ),
            'status'             => __( 'Status', 'ad-materials-calculator' ),
            'preview'            => '<span class="screen-reader-text">' . esc_html__( 'Preview', 'ad-materials-calculator' ) . '</span>',
            'created_by_user_id' => __( 'Created By', 'ad-materials-calculator' ),
            'created_at'         => __( 'Created', 'ad-materials-calculator' ),
            'expires_at'         => __( 'Expires', 'ad-materials-calculator' ),
            'access_count'       => __( 'Opens', 'ad-materials-calculator' ),
            'first_accessed_at'  => __( 'First Opened', 'ad-materials-calculator' ),
            'last_accessed_at'   => __( 'Last Opened', 'ad-materials-calculator' ),
            'used_order_id'      => __( 'Order', 'ad-materials-calculator' ),
        ];
    }

    /**
     * Define sortable columns.
     *
     * @since    1.0.0
     * @return   array
     */
    protected function get_sortable_columns() {

        return [
            'id'                => ['id', false],
            'created_at'        => ['created_at', true],
            'expires_at'        => ['expires_at', false],
            'access_count'      => ['access_count', false],
            'first_accessed_at' => ['first_accessed_at', false],
            'last_accessed_at'  => ['last_accessed_at', false],
            'used_order_id'     => ['used_order_id', false],
        ];
    }

    /**
     * Prepare share links for display.
     *
     * @since    1.0.0
     */
    public function prepare_items() {

        global $wpdb;

        $per_page     = $this->get_items_per_page( 'ad_materials_calculator_share_links_per_page', 20 );
        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;

        $columns  = $this->get_columns();
        $hidden   = get_hidden_columns( get_current_screen() );
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable, 'summary'];

        $table_name = Ad_Materials_Calculator_Share_Links::get_table_name();
        $users      = $wpdb->users;
        $search     = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
        $where      = '';
        $args       = [];

        if ( $search !== '' ) {
            $search_like = '%' . $wpdb->esc_like( $search ) . '%';
            $where_parts = [
                'CAST(share_links.id AS CHAR) LIKE %s',
                'CAST(share_links.used_order_id AS CHAR) LIKE %s',
                'share_links.token LIKE %s',
                'share_links.payload LIKE %s',
                'users.user_login LIKE %s',
                'users.display_name LIKE %s',
                'users.user_email LIKE %s',
            ];
            $args  = [$search_like, $search_like, $search_like, $search_like, $search_like, $search_like, $search_like];
            $token = $this->get_token_from_search_term( $search );

            if ( $token !== '' ) {
                $where_parts[] = 'share_links.token = %s';
                $args[]        = $token;
            }

            $where = 'WHERE ' . implode( ' OR ', $where_parts );
        }

        $allowed_orderby = [
            'id'                => 'share_links.id',
            'created_at'        => 'share_links.created_at',
            'expires_at'        => 'share_links.expires_at',
            'access_count'      => 'share_links.access_count',
            'first_accessed_at' => 'share_links.first_accessed_at',
            'last_accessed_at'  => 'share_links.last_accessed_at',
            'used_order_id'     => 'share_links.used_order_id',
        ];

        $orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
        $orderby = isset( $allowed_orderby[$orderby] ) ? $allowed_orderby[$orderby] : $allowed_orderby['created_at'];
        $order   = isset( $_REQUEST['order'] ) && strtolower( sanitize_key( wp_unslash( $_REQUEST['order'] ) ) ) === 'asc' ? 'ASC' : 'DESC';

        $count_sql = "SELECT COUNT(*) FROM {$table_name} share_links LEFT JOIN {$users} users ON users.ID = share_links.created_by_user_id {$where}";
        $total     = empty( $args ) ? (int) $wpdb->get_var( $count_sql ) : (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $args ) );

        $query_args = array_merge( $args, [$per_page, $offset] );
        $query_sql  = "SELECT share_links.*, users.display_name AS created_by_display_name, users.user_login AS created_by_user_login FROM {$table_name} share_links LEFT JOIN {$users} users ON users.ID = share_links.created_by_user_id {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        $this->items = $wpdb->get_results( $wpdb->prepare( $query_sql, $query_args ), ARRAY_A );

        $this->set_pagination_args(
            [
                'total_items' => $total,
                'per_page'    => $per_page,
                'total_pages' => ceil( $total / $per_page ),
            ]
        );
    }

    /**
     * Render a fallback column value.
     *
     * @since    1.0.0
     * @param    array  $item Share-link row.
     * @param    string $column_name Column slug.
     * @return   string
     */
    protected function column_default( $item, $column_name ) {

        if ( isset( $item[$column_name] ) ) {
            return esc_html( (string) $item[$column_name] );
        }

        return '';
    }

    /**
     * Render the share-link ID column.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_id( $item ) {

        return '#' . absint( $item['id'] );
    }

    /**
     * Render the preview column.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_preview( $item ) {

        $share_id = absint( isset( $item['id'] ) ? $item['id'] : 0 );
        $details  = $this->render_preview_details( $item );

        return sprintf(
            '<button type="button" class="button-link ad-mc-share-links-preview-button" data-ad-mc-share-links-preview="%1$d" aria-label="%2$s"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></button><div id="ad-mc-share-links-preview-%1$d" class="ad-mc-share-links-preview-template" hidden>%3$s</div>',
            $share_id,
            esc_attr__( 'Preview share link details', 'ad-materials-calculator' ),
            $details
        );
    }

    /**
     * Render the decoded cart summary column.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_summary( $item ) {

        $payload = Ad_Materials_Calculator_Share_Links::get_payload_from_record( $item );
        $groups  = $this->get_payload_groups( $payload );

        if ( empty( $groups ) ) {
            return '<em>' . esc_html__( 'Unavailable payload', 'ad-materials-calculator' ) . '</em>';
        }

        $titles            = [];
        $calculation_count = 0;
        $standalone_count  = 0;

        foreach ( $groups as $group ) {
            $title = ! empty( $group['project_title'] ) ? trim( (string) $group['project_title'] ) : '';

            if ( $title === '' ) {
                $title = __( 'Untitled calculation', 'ad-materials-calculator' );
            }

            if ( ! empty( $group['ad_materials_calc_is_standalone'] ) ) {
                $standalone_count++;
            } else {
                $calculation_count++;
            }

            $titles[] = $title;
        }

        $summary_parts = [];

        if ( $calculation_count > 0 ) {
            $summary_parts[] = sprintf(
                _n( '%d calculation group', '%d calculation groups', $calculation_count, 'ad-materials-calculator' ),
                $calculation_count
            );
        }

        if ( $standalone_count > 0 ) {
            $summary_parts[] = sprintf(
                _n( '%d product', '%d products', $standalone_count, 'ad-materials-calculator' ),
                $standalone_count
            );
        }

        $summary = ! empty( $summary_parts ) ? implode( ' + ', $summary_parts ) : sprintf(
            _n( '%d entry', '%d entries', count( $groups ), 'ad-materials-calculator' ),
            count( $groups )
        );

        $html = '<strong class="ad-mc-share-links-summary-count">' . esc_html( $summary ) . '</strong>';
        $html .= '<ul class="ad-mc-share-links-summary-list">';

        foreach ( $titles as $title ) {
            $html .= '<li>' . esc_html( $title ) . '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Render the status column.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_status( $item ) {

        return esc_html( $this->get_status_label( $item ) );
    }

    /**
     * Render the created by column.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_created_by_user_id( $item ) {

        $name  = ! empty( $item['created_by_display_name'] ) ? (string) $item['created_by_display_name'] : '';
        $login = ! empty( $item['created_by_user_login'] ) ? (string) $item['created_by_user_login'] : '';

        if ( $name === '' && $login === '' ) {
            return esc_html__( 'Unknown user', 'ad-materials-calculator' );
        }

        if ( $name !== '' && $login !== '' ) {
            return esc_html( $name . ' (' . $login . ')' );
        }

        return esc_html( $name !== '' ? $name : $login );
    }

    /**
     * Render created date.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_created_at( $item ) {

        return $this->format_date( isset( $item['created_at'] ) ? $item['created_at'] : '' );
    }

    /**
     * Render expiry date.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_expires_at( $item ) {

        return $this->format_date( isset( $item['expires_at'] ) ? $item['expires_at'] : '' );
    }

    /**
     * Render access count.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_access_count( $item ) {

        return esc_html( (string) absint( isset( $item['access_count'] ) ? $item['access_count'] : 0 ) );
    }

    /**
     * Render first accessed date.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_first_accessed_at( $item ) {

        return $this->format_date( isset( $item['first_accessed_at'] ) ? $item['first_accessed_at'] : '' );
    }

    /**
     * Render last accessed date.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_last_accessed_at( $item ) {

        return $this->format_date( isset( $item['last_accessed_at'] ) ? $item['last_accessed_at'] : '' );
    }

    /**
     * Render linked order.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    protected function column_used_order_id( $item ) {

        $order_id = absint( isset( $item['used_order_id'] ) ? $item['used_order_id'] : 0 );

        if ( ! $order_id ) {
            return '&mdash;';
        }

        $order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
        $url   = $order && method_exists( $order, 'get_edit_order_url' ) ? $order->get_edit_order_url() : admin_url( 'post.php?post=' . $order_id . '&action=edit' );

        return '<a href="' . esc_url( $url ) . '">#' . esc_html( (string) $order_id ) . '</a>';
    }

    /**
     * Render the detailed preview content for a share-link row.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    private function render_preview_details( array $item ) {

        $payload = Ad_Materials_Calculator_Share_Links::get_payload_from_record( $item );
        $groups  = $this->get_payload_groups( $payload );

        if ( empty( $groups ) ) {
            return '<p><em>' . esc_html__( 'No readable cart payload was found for this share link.', 'ad-materials-calculator' ) . '</em></p>';
        }

        $html = '<div class="ad-mc-share-links-preview">';
        $html .= '<div class="ad-mc-share-links-preview__meta">';
        $html .= '<span><strong>' . esc_html__( 'Status:', 'ad-materials-calculator' ) . '</strong> ' . esc_html( $this->get_status_label( $item ) ) . '</span>';
        $html .= '<span><strong>' . esc_html__( 'Created:', 'ad-materials-calculator' ) . '</strong> ' . $this->format_date( isset( $item['created_at'] ) ? $item['created_at'] : '' ) . '</span>';
        $html .= '<span><strong>' . esc_html__( 'Expires:', 'ad-materials-calculator' ) . '</strong> ' . $this->format_date( isset( $item['expires_at'] ) ? $item['expires_at'] : '' ) . '</span>';
        $html .= '<span><strong>' . esc_html__( 'Opens:', 'ad-materials-calculator' ) . '</strong> ' . esc_html( (string) absint( isset( $item['access_count'] ) ? $item['access_count'] : 0 ) ) . '</span>';
        $html .= '</div>';
        $html .= $this->render_preview_share_url( $item );

        foreach ( $groups as $index => $group ) {
            $html .= $this->render_preview_group( $group, $index + 1 );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render the recoverable public share URL for a row.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    private function render_preview_share_url( array $item ) {

        $share_url = Ad_Materials_Calculator_Share_Links::get_url_from_record( $item );

        if ( $share_url === '' ) {
            return '<p class="ad-mc-share-links-preview__missing-url"><em>' . esc_html__( 'The original share link is not recoverable for this record.', 'ad-materials-calculator' ) . '</em></p>';
        }

        $field_id = 'ad-mc-share-link-url-' . absint( isset( $item['id'] ) ? $item['id'] : 0 );

        $html = '<div class="ad-mc-share-links-preview__url-row">';
        $html .= '<label for="' . esc_attr( $field_id ) . '">' . esc_html__( 'Share link', 'ad-materials-calculator' ) . '</label>';
        $html .= '<input type="text" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $share_url ) . '" readonly>';
        $html .= '<button type="button" class="button ad-mc-share-links-preview__copy-url" data-ad-mc-copy-share-link data-copy-label="' . esc_attr__( 'Copy', 'ad-materials-calculator' ) . '" data-copied-label="' . esc_attr__( 'Copied', 'ad-materials-calculator' ) . '">' . esc_html__( 'Copy', 'ad-materials-calculator' ) . '</button>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Return normalized groups from a stored share-link payload.
     *
     * @since    1.0.0
     * @param    array $payload Stored share-link payload.
     * @return   array
     */
    private function get_payload_groups( array $payload ) {

        $groups = ! empty( $payload['groups'] ) && is_array( $payload['groups'] ) ? $payload['groups'] : [];

        if ( ! empty( $payload['items'] ) && is_array( $payload['items'] ) ) {
            foreach ( $payload['items'] as $item ) {
                $sku     = ! empty( $item['sku'] ) ? trim( (string) $item['sku'] ) : '';
                $product = $sku !== '' ? $this->get_product_by_sku( $sku ) : false;
                $title   = $product ? $this->get_product_display_name( $product ) : __( 'Unknown product', 'ad-materials-calculator' );

                $groups[] = [
                    'project_title'                   => $title,
                    'project_meta'                    => '',
                    'items'                           => [$item],
                    'ad_materials_calc_is_standalone' => true,
                ];
            }
        }

        if ( ! empty( $groups ) ) {
            return $groups;
        }

        return [];
    }

    /**
     * Return a token when the search term is a share URL or raw token.
     *
     * @since    1.0.0
     * @param    string $search Search term.
     * @return   string
     */
    private function get_token_from_search_term( $search ) {

        $search = trim( (string) $search );

        if ( $search === '' ) {
            return '';
        }

        $candidates = array_unique( [$search, rawurldecode( $search )] );

        foreach ( $candidates as $candidate ) {
            $parts = wp_parse_url( $candidate );

            if ( ! empty( $parts['query'] ) ) {
                parse_str( $parts['query'], $query_args );

                if ( ! empty( $query_args['mc_share'] ) ) {
                    return trim( (string) $query_args['mc_share'] );
                }
            }

            if ( preg_match( '/^[A-Za-z0-9]{20,}$/', $candidate ) ) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * Render one calculation group inside the preview modal.
     *
     * @since    1.0.0
     * @param    array $group Group payload.
     * @param    int   $index Group number.
     * @return   string
     */
    private function render_preview_group( array $group, $index ) {

        $title = ! empty( $group['project_title'] ) ? trim( (string) $group['project_title'] ) : '';

        if ( $title === '' ) {
            $title = sprintf(
                /* translators: %d: group number */
                __( 'Calculation %d', 'ad-materials-calculator' ),
                (int) $index
            );
        }

        $html = '<section class="ad-mc-share-links-preview-group">';
        $html .= $this->render_preview_group_products( $group, $title );
        $html .= '</section>';

        return $html;
    }

    /**
     * Render a group discount line.
     *
     * @since    1.0.0
     * @param    array $group Group payload.
     * @return   string
     */
    private function render_preview_group_discount( array $group ) {

        $discount_type  = ! empty( $group['discount_type'] ) ? (string) $group['discount_type'] : 'none';
        $discount_label = ! empty( $group['discount_label'] ) ? (string) $group['discount_label'] : __( 'Discount', 'ad-materials-calculator' );
        $requested      = isset( $group['discount_requested_value'] ) ? (float) $group['discount_requested_value'] : 0.0;

        if ( $discount_type === 'none' || $requested <= 0 ) {
            return '';
        }

        if ( $discount_type === 'percentage' ) {
            $value = rtrim( rtrim( number_format( $requested, 2, '.', '' ), '0' ), '.' ) . '%';
        } elseif ( $discount_type === 'fixed' && function_exists( 'wc_price' ) ) {
            $value = wp_strip_all_tags( wc_price( $requested ) );
        } else {
            $value = rtrim( rtrim( number_format( $requested, 2, '.', '' ), '0' ), '.' );
        }

        return '<div class="ad-mc-share-links-preview-group__discount">' . esc_html( $discount_label . ': ' . $value ) . '</div>';
    }

    /**
     * Render the products included in one calculation group.
     *
     * @since    1.0.0
     * @param    array $items Group product items.
     * @return   string
     */
    private function render_preview_group_products( array $group, $title ) {

        $items = ! empty( $group['items'] ) && is_array( $group['items'] ) ? $group['items'] : [];

        if ( empty( $items ) ) {
            return '<p><em>' . esc_html__( 'No products are stored for this calculation.', 'ad-materials-calculator' ) . '</em></p>';
        }

        $html = '<table class="ad-mc-share-links-preview-products">';
        $html .= '<colgroup>';
        $html .= '<col class="ad-mc-share-links-preview-products__product-column">';
        $html .= '<col class="ad-mc-share-links-preview-products__price-column">';
        $html .= '<col class="ad-mc-share-links-preview-products__quantity-column">';
        $html .= '<col class="ad-mc-share-links-preview-products__subtotal-column">';
        $html .= '</colgroup>';
        $html .= '<thead><tr>';
        $html .= '<th>' . esc_html__( 'Product', 'ad-materials-calculator' ) . '</th>';
        $html .= '<th>' . esc_html__( 'Price', 'ad-materials-calculator' ) . '</th>';
        $html .= '<th>' . esc_html__( 'Quantity', 'ad-materials-calculator' ) . '</th>';
        $html .= '<th>' . esc_html__( 'Subtotal', 'ad-materials-calculator' ) . '</th>';
        $html .= '</tr></thead><tbody>';
        $html .= '<tr class="ad-mc-share-links-preview-products__group-meta-row">';
        $html .= '<th colspan="4" scope="colgroup">';
        $html .= '<div class="ad-mc-share-links-preview-group__header">';
        $html .= '<h3>' . esc_html( $title ) . '</h3>';

        if ( ! empty( $group['project_meta'] ) ) {
            $html .= '<div class="ad-mc-share-links-preview-group__meta">' . wp_kses_post( $group['project_meta'] ) . '</div>';
        }

        $html .= $this->render_preview_group_discount( $group );
        $html .= '</div>';
        $html .= '</th>';
        $html .= '</tr>';

        foreach ( $items as $item ) {
            $sku     = ! empty( $item['sku'] ) ? trim( (string) $item['sku'] ) : '';
            $qty     = isset( $item['qty'] ) ? max( 0, (int) $item['qty'] ) : 0;
            $product = $sku !== '' ? $this->get_product_by_sku( $sku ) : false;
            $price   = $product ? (float) wc_get_price_to_display( $product ) : 0.0;

            $html .= '<tr>';
            $html .= '<td>' . $this->render_preview_product_name( $product, $sku ) . '</td>';
            $html .= '<td>' . wp_kses_post( function_exists( 'wc_price' ) ? wc_price( $price ) : number_format( $price, 2 ) ) . '</td>';
            $html .= '<td>' . esc_html( (string) $qty ) . '</td>';
            $html .= '<td>' . wp_kses_post( function_exists( 'wc_price' ) ? wc_price( $price * $qty ) : number_format( $price * $qty, 2 ) ) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Render product name, SKU, and thumbnail for the preview table.
     *
     * @since    1.0.0
     * @param    WC_Product|false $product Product object.
     * @param    string           $sku Product SKU.
     * @return   string
     */
    private function render_preview_product_name( $product, $sku ) {

        $name = $product ? $this->get_product_display_name( $product ) : __( 'Unknown product', 'ad-materials-calculator' );
        $html = '<div class="ad-mc-share-links-preview-product">';
        $html .= $this->get_product_thumb_html( $product );
        $html .= '<div>';
        $html .= '<strong>' . esc_html( $name ) . '</strong>';

        if ( $sku !== '' ) {
            $html .= '<div class="ad-mc-share-links-preview-product__sku">' . esc_html( $sku ) . '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Return a product by SKU.
     *
     * @since    1.0.0
     * @param    string $sku Product SKU.
     * @return   WC_Product|false
     */
    private function get_product_by_sku( $sku ) {

        static $product_cache = [];

        $sku = trim( (string) $sku );

        if ( $sku === '' ) {
            return false;
        }

        if ( array_key_exists( $sku, $product_cache ) ) {
            return $product_cache[$sku];
        }

        $product_id = function_exists( 'wc_get_product_id_by_sku' ) ? wc_get_product_id_by_sku( $sku ) : 0;

        if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
            $product_cache[$sku] = false;

            return false;
        }

        $product_cache[$sku] = wc_get_product( $product_id );

        return $product_cache[$sku];
    }

    /**
     * Return a readable product display name.
     *
     * @since    1.0.0
     * @param    WC_Product $product Product object.
     * @return   string
     */
    private function get_product_display_name( $product ) {

        if ( ! $product ) {
            return '';
        }

        if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variation' ) ) {
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

    /**
     * Return product thumbnail HTML for the preview table.
     *
     * @since    1.0.0
     * @param    WC_Product|false $product Product object.
     * @return   string
     */
    private function get_product_thumb_html( $product ) {

        if ( ! $product ) {
            return '';
        }

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

        return wp_get_attachment_image( $image_id, 'thumbnail', false, ['class' => 'ad-mc-share-links-preview-product__thumb'] );
    }

    /**
     * Return the share-link status label.
     *
     * @since    1.0.0
     * @param    array $item Share-link row.
     * @return   string
     */
    private function get_status_label( array $item ) {

        if ( ! empty( $item['used_order_id'] ) ) {
            return __( 'Converted', 'ad-materials-calculator' );
        }

        if ( ! empty( $item['expires_at'] ) && strtotime( $item['expires_at'] ) <= current_time( 'timestamp', true ) ) {
            return __( 'Expired', 'ad-materials-calculator' );
        }

        if ( ! empty( $item['access_count'] ) ) {
            return __( 'Opened', 'ad-materials-calculator' );
        }

        return __( 'Active', 'ad-materials-calculator' );
    }

    /**
     * Format a GMT database date for admin display.
     *
     * @since    1.0.0
     * @param    string $date Date string.
     * @return   string
     */
    private function format_date( $date ) {

        $date = is_string( $date ) ? trim( $date ) : '';

        if ( $date === '' || $date === '0000-00-00 00:00:00' ) {
            return '&mdash;';
        }

        return esc_html( get_date_from_gmt( $date, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) );
    }
}