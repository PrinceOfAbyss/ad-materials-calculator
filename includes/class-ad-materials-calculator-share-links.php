<?php

/**
 * Share-link storage for calculated materials carts.
 *
 * @link       https://agencydigital.tech/
 * @since      1.0.0
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/includes
 */

/**
 * Handles persistent share-link records for calculated cart payloads.
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/includes
 * @author     Agency Digital <info@agencydigital.tech>
 */
class Ad_Materials_Calculator_Share_Links {

    /**
     * Return the database table name for share-link records.
     *
     * @since    1.0.0
     * @return   string
     */
    public static function get_table_name() {

        global $wpdb;

        return $wpdb->prefix . 'ad_materials_calculator_share_links';
    }

    /**
     * Create or update the share-link database table.
     *
     * @since    1.0.0
     */
    public static function install_table() {

        global $wpdb;

        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta(
            "CREATE TABLE {$table_name} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                token varchar(64) NOT NULL,
                payload longtext NOT NULL,
                created_by_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                expires_at datetime NOT NULL,
                used_order_id bigint(20) unsigned NOT NULL DEFAULT 0,
                used_at datetime NULL DEFAULT NULL,
                access_count bigint(20) unsigned NOT NULL DEFAULT 0,
                first_accessed_at datetime NULL DEFAULT NULL,
                last_accessed_at datetime NULL DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY token (token),
                KEY created_by_user_id (created_by_user_id),
                KEY expires_at (expires_at),
                KEY used_order_id (used_order_id),
                KEY first_accessed_at (first_accessed_at),
                KEY last_accessed_at (last_accessed_at)
            ) {$charset_collate};"
        );
    }

    /**
     * Create a share-link record from a calculator cart payload.
     *
     * @since    1.0.0
     * @param    array $payload Cart payload.
     * @return   array
     */
    public static function create( array $payload ) {

        global $wpdb;

        $token      = wp_generate_password( 48, false, false );
        $created_at = current_time( 'mysql', true );
        $expires_at = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) + ( 30 * DAY_IN_SECONDS ) );
        $payload    = wp_json_encode( $payload );

        if ( ! is_string( $payload ) || '' === $payload ) {
            return [];
        }

        $inserted = $wpdb->insert(
            self::get_table_name(),
            [
                'token'              => $token,
                'payload'            => $payload,
                'created_by_user_id' => get_current_user_id(),
                'created_at'         => $created_at,
                'expires_at'         => $expires_at,
            ],
            [
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
            ]
        );

        if ( ! $inserted ) {
            return [];
        }

        return [
            'id'         => (int) $wpdb->insert_id,
            'token'      => $token,
            'url'        => self::get_url_from_token( $token ),
            'expires_at' => $expires_at,
        ];
    }

    /**
     * Return a share-link record by public token.
     *
     * @since    1.0.0
     * @param    string $token Public token.
     * @return   array
     */
    public static function get_by_token( $token ) {

        global $wpdb;

        $token = is_string( $token ) ? trim( $token ) : '';

        if ( '' === $token ) {
            return [];
        }

        $record = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . self::get_table_name() . ' WHERE token = %s LIMIT 1',
                $token
            ),
            ARRAY_A
        );

        return is_array( $record ) ? $record : [];
    }

    /**
     * Build a public share URL from a stored token.
     *
     * @since    1.0.0
     * @param    string $token Public token.
     * @return   string
     */
    public static function get_url_from_token( $token ) {

        $token = is_string( $token ) ? trim( $token ) : '';

        if ( '' === $token ) {
            return '';
        }

        return add_query_arg( 'mc_share', rawurlencode( $token ), home_url( '/materials-calculator/' ) );
    }

    /**
     * Build a public share URL from a share-link database record.
     *
     * @since    1.0.0
     * @param    array $record Share-link record.
     * @return   string
     */
    public static function get_url_from_record( array $record ) {

        return self::get_url_from_token( isset( $record['token'] ) ? (string) $record['token'] : '' );
    }

    /**
     * Decode the stored payload for a share-link record.
     *
     * @since    1.0.0
     * @param    array $record Share-link record.
     * @return   array
     */
    public static function get_payload_from_record( array $record ) {

        if ( empty( $record['payload'] ) || ! is_string( $record['payload'] ) ) {
            return [];
        }

        $payload = json_decode( $record['payload'], true );

        return is_array( $payload ) ? $payload : [];
    }

    /**
     * Determine whether a share-link record is usable.
     *
     * @since    1.0.0
     * @param    array $record Share-link record.
     * @return   bool
     */
    public static function is_record_valid( array $record ) {

        if ( empty( $record['id'] ) || empty( $record['expires_at'] ) ) {
            return false;
        }

        if ( ! empty( $record['used_order_id'] ) ) {
            return false;
        }

        return strtotime( $record['expires_at'] ) > current_time( 'timestamp', true );
    }

    /**
     * Mark a share-link record as accessed.
     *
     * @since    1.0.0
     * @param    int $share_id Share-link record ID.
     */
    public static function mark_accessed( $share_id ) {

        global $wpdb;

        $share_id = absint( $share_id );

        if ( ! $share_id ) {
            return;
        }

        $now = current_time( 'mysql', true );

        $wpdb->query(
            $wpdb->prepare(
                'UPDATE ' . self::get_table_name() . ' SET access_count = access_count + 1, first_accessed_at = IF(first_accessed_at IS NULL, %s, first_accessed_at), last_accessed_at = %s WHERE id = %d',
                $now,
                $now,
                $share_id
            )
        );
    }

    /**
     * Mark a share-link record as used by an order.
     *
     * @since    1.0.0
     * @param    int $share_id Share-link record ID.
     * @param    int $order_id WooCommerce order ID.
     */
    public static function mark_used_by_order( $share_id, $order_id ) {

        global $wpdb;

        $share_id = absint( $share_id );
        $order_id = absint( $order_id );

        if ( ! $share_id || ! $order_id ) {
            return;
        }

        $wpdb->update(
            self::get_table_name(),
            [
                'used_order_id' => $order_id,
                'used_at'       => current_time( 'mysql', true ),
            ],
            [
                'id' => $share_id,
            ],
            [
                '%d',
                '%s',
            ],
            [
                '%d',
            ]
        );
    }

}