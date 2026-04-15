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
                token_hash varchar(64) NOT NULL,
                payload longtext NOT NULL,
                created_by_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                expires_at datetime NOT NULL,
                used_order_id bigint(20) unsigned NOT NULL DEFAULT 0,
                used_at datetime NULL DEFAULT NULL,
                last_accessed_at datetime NULL DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY token_hash (token_hash),
                KEY expires_at (expires_at),
                KEY used_order_id (used_order_id)
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

        self::install_table();

        $token      = wp_generate_password( 48, false, false );
        $token_hash = self::hash_token( $token );
        $created_at = current_time( 'mysql', true );
        $expires_at = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) + ( 30 * DAY_IN_SECONDS ) );
        $payload    = wp_json_encode( $payload );

        if ( ! is_string( $payload ) || '' === $payload ) {
            return [];
        }

        $inserted = $wpdb->insert(
            self::get_table_name(),
            [
                'token_hash'         => $token_hash,
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
            'token_hash' => $token_hash,
            'url'        => add_query_arg( 'mc_share', rawurlencode( $token ), home_url( '/materials-calculator/' ) ),
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
                'SELECT * FROM ' . self::get_table_name() . ' WHERE token_hash = %s LIMIT 1',
                self::hash_token( $token )
            ),
            ARRAY_A
        );

        return is_array( $record ) ? $record : [];
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

        $wpdb->update(
            self::get_table_name(),
            [
                'last_accessed_at' => current_time( 'mysql', true ),
            ],
            [
                'id' => $share_id,
            ],
            [
                '%s',
            ],
            [
                '%d',
            ]
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

    /**
     * Hash a public share token for storage and lookup.
     *
     * @since    1.0.0
     * @param    string $token Public token.
     * @return   string
     */
    public static function hash_token( $token ) {

        return hash( 'sha256', (string) $token );
    }
}