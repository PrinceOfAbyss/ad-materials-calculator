<?php

/**
 * The WooCommerce settings functionality for the plugin.
 *
 * @link       https://agencydigital.tech/
 * @since      1.0.0
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/admin
 */

/**
 * Handles the Materials Calculator settings tab and nested coverage editor.
 *
 * @package    Ad_Materials_Calculator
 * @subpackage Ad_Materials_Calculator/admin
 * @author     Agency Digital <info@agencydigital.tech>
 */
class Ad_Materials_Calculator_Settings {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $version;

    /**
     * Option name used to persist the coverage settings JSON blob.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private $coverages_option_name = 'ad_materials_calculator_coverages_json';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string $plugin_name The name of this plugin.
     * @param    string $version The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /**
     * Add the Materials Calculator tab to WooCommerce settings.
     *
     * @since    1.0.0
     * @param    array $tabs Existing WooCommerce settings tabs.
     * @return   array
     */
    public function add_woocommerce_settings_tab( $tabs ) {

        $tabs['materials-calculator'] = __( 'Materials Calculator', 'ad-materials-calculator' );

        return $tabs;
    }

    /**
     * Render the custom Materials Calculator WooCommerce settings tab.
     *
     * @since    1.0.0
     */
    public function render_woocommerce_settings_tab() {

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage WooCommerce settings.', 'ad-materials-calculator' ) );
        }

        $active_section = $this->get_active_system_section();
        $all_schema     = Ad_Materials_Calculator_Coverage_Schema::get_coverages_settings_schema();
        $schema         = Ad_Materials_Calculator_Coverage_Schema::filter_schema_for_system_section( $all_schema, $active_section );
        $stored_values  = $this->get_saved_coverages_settings();

        echo '<div class="ad-mc-settings-page">';
        echo '<h2>' . esc_html__( 'Materials Calculator Coverage Settings', 'ad-materials-calculator' ) . '</h2>';
        $this->render_system_sections_nav( $active_section );

        foreach ( $schema as $case_key => $case_config ) {
            echo '<section class="ad-mc-settings-case">';
            echo '<header class="ad-mc-settings-case__header">';
            echo '<h3>' . esc_html( $case_config['title'] ) . '</h3>';
            echo '</header>';

            $show_clause_headings = count( $case_config['clauses'] ) > 1;

            foreach ( $case_config['clauses'] as $clause_key => $clause_config ) {
                echo '<div class="ad-mc-settings-clause">';
                if ( $show_clause_headings ) {
                    echo '<div class="ad-mc-settings-clause__heading">';
                    echo '<h4>' . esc_html( $clause_config['title'] ) . '</h4>';
                    if ( ! empty( $clause_config['description'] ) ) {
                        echo '<p>' . esc_html( $clause_config['description'] ) . '</p>';
                    }
                    echo '</div>';
                }

                echo '<table class="widefat striped ad-mc-settings-table">';
                echo '<thead><tr>';
                echo '<th>' . esc_html__( 'Material', 'ad-materials-calculator' ) . '</th>';
                echo '<th>' . esc_html__( 'Coverage 1', 'ad-materials-calculator' ) . '</th>';
                echo '<th>' . esc_html__( 'Coverage 2', 'ad-materials-calculator' ) . '</th>';
                echo '<th>' . esc_html__( 'Coverage 3', 'ad-materials-calculator' ) . '</th>';
                echo '<th>' . esc_html__( 'Total', 'ad-materials-calculator' ) . '</th>';
                echo '</tr></thead>';
                echo '<tbody>';

                foreach ( $clause_config['materials'] as $material_key => $material_config ) {
                    $row_values = $this->get_material_row_values(
                        $stored_values,
                        $case_key,
                        $clause_key,
                        $active_section,
                        $material_key,
                        $material_config['values']
                    );

                    echo '<tr>';
                    echo '<td class="ad-mc-settings-table__label">';
                    echo '<span class="ad-mc-settings-table__material-name">' . esc_html( $material_config['label'] ) . '</span>';
                    if ( ! empty( $material_config['hint'] ) ) {
                        echo '<span class="ad-mc-settings-table__material-hint">' . esc_html( $material_config['hint'] ) . '</span>';
                    }
                    echo '</td>';

                    for ( $i = 0; $i < 3; $i++ ) {
                        $field_name  = sprintf( 'ad_materials_calculator_coverages[%s][%s][%s][%s][%d]', $case_key, $clause_key, $active_section, $material_key, $i );
                        $field_value = isset( $row_values[$i] ) ? $row_values[$i] : '';

                        echo '<td>';
                        printf(
                            '<input type="number" step="0.001" min="0" class="small-text ad-mc-settings-decimal" name="%1$s" value="%2$s" />',
                            esc_attr( $field_name ),
                            esc_attr( $field_value )
                        );
                        echo '</td>';
                    }

                    echo '<td class="ad-mc-settings-table__sum" data-row-sum>' . esc_html( $this->get_material_row_sum( $row_values ) ) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table>';
                echo '</div>';
            }

            echo '</section>';
        }

        echo '<input type="hidden" name="ad_materials_calculator_active_section" value="' . esc_attr( $active_section ) . '" />';
        echo '</div>';
    }

    /**
     * Save the custom Materials Calculator WooCommerce settings tab.
     *
     * @since    1.0.0
     */
    public function save_woocommerce_settings_tab() {

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $active_section = $this->get_active_system_section();
        $submitted      = isset( $_POST['ad_materials_calculator_coverages'] ) ? wp_unslash( $_POST['ad_materials_calculator_coverages'] ) : [];
        $all_schema     = Ad_Materials_Calculator_Coverage_Schema::get_coverages_settings_schema();
        $section_schema = Ad_Materials_Calculator_Coverage_Schema::filter_schema_for_system_section( $all_schema, $active_section );
        $sanitized      = $this->sanitize_coverages_settings( $submitted, $section_schema, $active_section );
        $existing       = $this->get_saved_coverages_settings();
        $merged         = $this->merge_coverages_settings( $existing, $sanitized );

        update_option( $this->coverages_option_name, wp_json_encode( $merged ) );

        WC_Admin_Settings::add_message( __( 'Materials Calculator coverage settings saved.', 'ad-materials-calculator' ) );
    }

    /**
     * Return the saved coverage settings as a decoded array.
     *
     * @since    1.0.0
     * @return   array
     */
    private function get_saved_coverages_settings() {

        $raw = get_option( $this->coverages_option_name, '' );

        if ( ! is_string( $raw ) || $raw === '' ) {
            return [];
        }

        $decoded = json_decode( $raw, true );

        return is_array( $decoded ) ? $decoded : [];
    }

    /**
     * Pick the stored or fallback values for one material row.
     *
     * @since    1.0.0
     * @param    array  $stored_values Saved option values.
     * @param    string $case_key Case key.
     * @param    string $clause_key Clause key.
     * @param    string $section Current system section.
     * @param    string $material_key Material key.
     * @param    array  $default_values Default row values.
     * @return   array
     */
    private function get_material_row_values( array $stored_values, $case_key, $clause_key, $section, $material_key, array $default_values ) {

        $stored_row = [];

        if ( isset( $stored_values[$case_key][$clause_key][$section][$material_key] ) && is_array( $stored_values[$case_key][$clause_key][$section][$material_key] ) ) {
            $stored_row = $stored_values[$case_key][$clause_key][$section][$material_key];
        }

        $row_values = [];

        for ( $i = 0; $i < 3; $i++ ) {
            if ( array_key_exists( $i, $stored_row ) ) {
                $row_values[$i] = $stored_row[$i];
            } elseif ( array_key_exists( $i, $default_values ) ) {
                $row_values[$i] = $default_values[$i];
            } else {
                $row_values[$i] = '';
            }
        }

        return $row_values;
    }

    /**
     * Build the display value for the Total column of one material row.
     *
     * The total is shown when at least one coverage field is filled.
     *
     * @since    1.0.0
     * @param    array $row_values Coverage row values.
     * @return   string
     */
    private function get_material_row_sum( array $row_values ) {

        $filled_values = [];

        foreach ( $row_values as $value ) {
            if ( $value === '' || $value === null ) {
                continue;
            }

            $filled_values[] = (float) $value;
        }

        if ( empty( $filled_values ) ) {
            return '';
        }

        return number_format( array_sum( $filled_values ), 3, '.', '' );
    }

    /**
     * Sanitize the nested coverage settings structure before saving.
     *
     * @since    1.0.0
     * @param    array $submitted Submitted values.
     * @param    array  $schema Settings schema.
     * @param    string $section Current system section.
     * @return   array
     */
    private function sanitize_coverages_settings( array $submitted, array $schema, $section ) {

        $sanitized = [];

        foreach ( $schema as $case_key => $case_config ) {
            foreach ( $case_config['clauses'] as $clause_key => $clause_config ) {
                $sanitized[$case_key][$clause_key][$section] = [];

                foreach ( $clause_config['materials'] as $material_key => $material_config ) {
                    $material_values = isset( $submitted[$case_key][$clause_key][$section][$material_key] ) && is_array( $submitted[$case_key][$clause_key][$section][$material_key] )
                    ? $submitted[$case_key][$clause_key][$section][$material_key]
                    : [];

                    $sanitized_row = [];

                    for ( $i = 0; $i < 3; $i++ ) {
                        $value = isset( $material_values[$i] ) ? trim( (string) $material_values[$i] ) : '';

                        if ( $value === '' ) {
                            $sanitized_row[] = '';
                            continue;
                        }

                        $sanitized_row[] = $this->sanitize_decimal_string( $value );
                    }

                    $sanitized[$case_key][$clause_key][$section][$material_key] = $sanitized_row;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Normalize a decimal string to a simple 3-decimal format.
     *
     * @since    1.0.0
     * @param    string $value Raw value.
     * @return   string
     */
    private function sanitize_decimal_string( $value ) {

        $normalized = str_replace( ',', '.', $value );
        $number     = (float) $normalized;

        return number_format( $number, 3, '.', '' );
    }

    /**
     * Get the currently active system section.
     *
     * @since    1.0.0
     * @return   string
     */
    private function get_active_system_section() {

        $section = '';

        if ( isset( $_POST['ad_materials_calculator_active_section'] ) ) {
            $section = sanitize_key( wp_unslash( $_POST['ad_materials_calculator_active_section'] ) );
        } elseif ( isset( $_GET['section'] ) ) {
            $section = sanitize_key( wp_unslash( $_GET['section'] ) );
        }

        if ( ! in_array( $section, ['medium', 'fine', 'microterrazzo'], true ) ) {
            $section = 'medium';
        }

        return $section;
    }

    /**
     * Render the system sub-navigation using WooCommerce's native section style.
     *
     * @since    1.0.0
     * @param    string $active_section Active section slug.
     */
    private function render_system_sections_nav( $active_section ) {

        $sections = [
            'medium'        => __( 'Medium', 'ad-materials-calculator' ),
            'fine'          => __( 'Fine', 'ad-materials-calculator' ),
            'microterrazzo' => __( 'Microterrazzo', 'ad-materials-calculator' ),
        ];

        echo '<ul class="subsubsub ad-mc-settings-sections">';

        $section_links = [];

        foreach ( $sections as $section_key => $section_label ) {
            $url             = admin_url( 'admin.php?page=wc-settings&tab=materials-calculator&section=' . $section_key );
            $current_class   = $active_section === $section_key ? 'current' : '';
            $section_links[] = sprintf(
                '<li><a href="%1$s" class="%2$s">%3$s</a></li>',
                esc_url( $url ),
                esc_attr( $current_class ),
                esc_html( $section_label )
            );
        }

        echo implode( ' | ', $section_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</ul>';
        echo '<br class="clear" />';
    }

    /**
     * Merge saved section values back into the full JSON structure.
     *
     * @since    1.0.0
     * @param    array $existing Previously saved values.
     * @param    array $updates Current section updates.
     * @return   array
     */
    private function merge_coverages_settings( array $existing, array $updates ) {

        foreach ( $updates as $case_key => $case_values ) {
            if ( ! isset( $existing[$case_key] ) || ! is_array( $existing[$case_key] ) ) {
                $existing[$case_key] = [];
            }

            foreach ( $case_values as $clause_key => $clause_values ) {
                if ( ! isset( $existing[$case_key][$clause_key] ) || ! is_array( $existing[$case_key][$clause_key] ) ) {
                    $existing[$case_key][$clause_key] = [];
                }

                foreach ( $clause_values as $section_key => $section_values ) {
                    if ( ! isset( $existing[$case_key][$clause_key][$section_key] ) || ! is_array( $existing[$case_key][$clause_key][$section_key] ) ) {
                        $existing[$case_key][$clause_key][$section_key] = [];
                    }

                    foreach ( $section_values as $material_key => $material_values ) {
                        $existing[$case_key][$clause_key][$section_key][$material_key] = $material_values;
                    }
                }
            }
        }

        return $existing;
    }

}